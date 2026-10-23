<?php

namespace App\Http\Middleware;

use App\Models\OperationLog;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * 后台操作日志中间件：自动记录后台所有写操作（POST/PUT/PATCH/DELETE），
 * 含操作人员、模块、类型、结果、请求参数、耗时与错误信息；GET 等查询请求不记录
 */
class LogOperation
{
    /**
     * 需要记录的 HTTP 动词
     *
     * @var list<string>
     */
    private const LOGGED_METHODS = ['POST', 'PUT', 'PATCH', 'DELETE'];

    /**
     * 请求参数中需要剔除的敏感字段关键词（字段名包含即剔除，大小写不敏感，任意层级生效）。
     * 宁可多剔除也不能让敏感信息落入日志
     *
     * @var list<string>
     */
    private const SENSITIVE_KEYS = [
        'password', 'passwd', 'pwd', 'secret', 'token', 'apikey', 'api_key',
        'access_key', 'private_key', 'credential', 'authorization', 'auth_code',
        'recovery', 'otp', 'code', 'cookie', 'session', 'card', 'cvv', 'ssn',
    ];

    /**
     * 处理请求：测量耗时并写操作日志，异常同样记录后原样抛出
     *
     * @param  Closure(Request): (SymfonyResponse)  $next
     */
    public function handle(Request $request, Closure $next): SymfonyResponse
    {
        if (! in_array(strtoupper($request->getMethod()), self::LOGGED_METHODS, true)) {
            return $next($request);
        }

        $start = microtime(true);

        try {
            $response = $next($request);
        } catch (\Throwable $e) {
            $this->record($request, null, microtime(true) - $start, $e->getMessage());

            throw $e;
        }

        $this->record($request, $response, microtime(true) - $start);

        return $response;
    }

    /**
     * 写入操作日志
     */
    private function record(Request $request, ?SymfonyResponse $response, float $elapsed, ?string $exceptionMessage = null): void
    {
        $user = $request->user();
        $routeName = (string) ($request->route()?->getName() ?? '');
        $module = OperationLog::resolveModule($routeName);
        $type = OperationLog::resolveType($request->getMethod());

        // 判定操作结果：异常、HTTP >= 400、重定向回退带验证错误均视为失败
        $errorMessage = $exceptionMessage;
        $status = $errorMessage === null;

        if ($response !== null && $response->getStatusCode() >= 400) {
            $status = false;
            $errorMessage = $response->getStatusCode().' '.$response->getContent();
        } elseif ($response instanceof RedirectResponse
            && $request->session()->has('errors')
        ) {
            // 非 JSON 请求验证失败时以 302 回退，从 session 中取首条错误
            $status = false;
            $errors = $request->session()->get('errors');
            $errorMessage = $errors->first();
        }

        OperationLog::create([
            'user_id' => $user?->id,
            'username' => (string) ($user?->name ?? ''),
            'module' => $module,
            'summary' => $module.' · '.$type,
            'type' => $type,
            'status' => $status,
            'url' => Str::limit($request->fullUrl(), 500, ''),
            'ip' => (string) $request->ip(),
            'parameters' => $this->collectParameters($request),
            'error_message' => $errorMessage !== null ? Str::limit($errorMessage, 1000, '') : null,
            'cost_ms' => (int) round($elapsed * 1000),
            'performed_at' => now(),
        ]);
    }

    /**
     * 采集请求参数：上传文件仅记录原始文件名，并递归剔除任意层级的敏感字段；
     * 参数为空（如删除、清空操作）时返回 null
     *
     * @return array<string, mixed>|null
     */
    private function collectParameters(Request $request): ?array
    {
        $parameters = $this->filterSensitive(
            (array) $this->normalizeValue($request->all()),
        );

        return $parameters === [] ? null : $parameters;
    }

    /**
     * 递归归一化参数值：上传文件替换为原始文件名，保证整体可 JSON 编码
     */
    private function normalizeValue(mixed $value): mixed
    {
        if ($value instanceof UploadedFile) {
            return $value->getClientOriginalName();
        }

        if (is_array($value)) {
            return array_map(fn (mixed $item): mixed => $this->normalizeValue($item), $value);
        }

        return $value;
    }

    /**
     * 递归剔除敏感字段：任意层级的键名命中关键词即整体丢弃，嵌套数组继续向下检查
     *
     * @param  array<mixed>  $parameters
     * @return array<mixed>
     */
    private function filterSensitive(array $parameters): array
    {
        $filtered = [];

        foreach ($parameters as $key => $value) {
            if (Str::contains(strtolower((string) $key), self::SENSITIVE_KEYS)) {
                continue;
            }

            $filtered[$key] = is_array($value)
                ? $this->filterSensitive($value)
                : $value;
        }

        return $filtered;
    }
}
