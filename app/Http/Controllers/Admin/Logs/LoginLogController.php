<?php

namespace App\Http\Controllers\Admin\Logs;

use App\Http\Controllers\Controller;
use App\Models\LoginLog;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * 登录日志控制器：列表、批量删除、清空、CSV 导出
 */
class LoginLogController extends Controller
{
    /**
     * 支持的登录状态筛选项
     */
    private const STATUS_FILTERS = ['success', 'fail'];

    /**
     * 登录日志列表页：支持账号关键字与登录状态筛选，按登录时间倒序分页
     */
    public function index(Request $request): Response
    {
        $logs = $this->baseQuery($request)
            ->orderBy('login_at', 'desc')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (LoginLog $log) => $this->mapLog($log));

        return Inertia::render('admin/logs/login/Index', [
            'logs' => $logs,
        ]);
    }

    /**
     * 批量删除选中日志
     *
     * @throws ValidationException
     */
    public function destroy(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        LoginLog::query()->whereIn('id', $validated['ids'])->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('日志已删除。')]);

        return to_route('admin.login-logs.index');
    }

    /**
     * 清空全部登录日志
     */
    public function clear(): RedirectResponse
    {
        LoginLog::query()->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('登录日志已清空。')]);

        return to_route('admin.login-logs.index');
    }

    /**
     * 按当前筛选条件导出全部日志为 CSV（UTF-8 BOM，Excel 直接打开不乱码）
     */
    public function export(Request $request): StreamedResponse
    {
        $query = $this->baseQuery($request)->orderBy('login_at', 'desc');

        return response()->streamDownload(function () use ($query): void {
            $output = fopen('php://output', 'w');

            // 写入 UTF-8 BOM，保证 Excel 识别中文编码
            fwrite($output, "\xEF\xBB\xBF");

            fputcsv($output, ['用户账号', '登录状态', 'IP 地址', '浏览器', '操作系统', '提示信息', '登录时间']);

            $query->chunk(500, function ($logs) use ($output): void {
                foreach ($logs as $log) {
                    fputcsv($output, [
                        $log->account,
                        $log->status ? '成功' : '失败',
                        $log->ip,
                        $log->browser,
                        $log->platform,
                        $log->message,
                        $log->login_at->format('Y-m-d H:i:s'),
                    ]);
                }
            });

            fclose($output);
        }, 'login-logs-'.now()->format('Ymd-His').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * 构建带筛选条件的查询：账号关键字模糊匹配 + 登录状态过滤
     */
    private function baseQuery(Request $request): Builder
    {
        $search = trim((string) $request->query('search', ''));
        $status = (string) $request->query('status', '');

        return LoginLog::query()
            ->when($search !== '', function (Builder $query) use ($search) {
                $query->where('account', 'like', "%{$search}%");
            })
            ->when(in_array($status, self::STATUS_FILTERS, true), function (Builder $query) use ($status) {
                $query->where('status', $status === 'success');
            });
    }

    /**
     * 将日志记录映射为前端输出结构
     *
     * @return array{id: int, account: string, status: bool, ip: string, browser: string, platform: string, message: string, login_at: string}
     */
    private function mapLog(LoginLog $log): array
    {
        return [
            'id' => $log->id,
            'account' => $log->account,
            'status' => $log->status,
            'ip' => $log->ip,
            'browser' => $log->browser,
            'platform' => $log->platform,
            'message' => $log->message,
            'login_at' => $log->login_at->format('Y-m-d H:i:s'),
        ];
    }
}
