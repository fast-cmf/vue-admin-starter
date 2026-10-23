<?php

namespace App\Http\Controllers\Admin\Logs;

use App\Http\Controllers\Controller;
use App\Models\OperationLog;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * 操作日志控制器：列表、批量删除、清空、CSV 导出
 */
class OperationLogController extends Controller
{
    /**
     * 支持的登录状态筛选项
     */
    private const STATUS_FILTERS = ['success', 'fail'];

    /**
     * 操作日志列表页：支持关键字与操作结果筛选，按操作时间倒序分页
     */
    public function index(Request $request): Response
    {
        $logs = $this->baseQuery($request)
            ->orderBy('performed_at', 'desc')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (OperationLog $log) => $this->mapLog($log));

        return Inertia::render('admin/logs/operation/Index', [
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

        OperationLog::query()->whereIn('id', $validated['ids'])->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('日志已删除。')]);

        return to_route('admin.operation-logs.index');
    }

    /**
     * 清空全部操作日志
     */
    public function clear(): RedirectResponse
    {
        OperationLog::query()->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('操作日志已清空。')]);

        return to_route('admin.operation-logs.index');
    }

    /**
     * 按当前筛选条件导出全部日志为 CSV（UTF-8 BOM，Excel 直接打开不乱码）
     */
    public function export(Request $request): StreamedResponse
    {
        $query = $this->baseQuery($request)->orderBy('performed_at', 'desc');

        return response()->streamDownload(function () use ($query): void {
            $output = fopen('php://output', 'w');

            // 写入 UTF-8 BOM，保证 Excel 识别中文编码
            fwrite($output, "\xEF\xBB\xBF");

            fputcsv($output, ['日志编号', '操作结果', '模块名称', '操作摘要', '操作类型', '操作人员', '请求地址', 'IP 地址', '请求参数', '错误信息', '耗时', '操作时间']);

            $query->chunk(500, function ($logs) use ($output): void {
                foreach ($logs as $log) {
                    fputcsv($output, [
                        $log->id,
                        $log->status ? '成功' : '失败',
                        $log->module,
                        $log->summary,
                        $log->type,
                        $log->username,
                        $log->url,
                        $log->ip,
                        $log->parameters !== null ? json_encode($log->parameters, JSON_UNESCAPED_UNICODE) : '',
                        $log->error_message ?? '',
                        $log->cost_ms.' ms',
                        $log->performed_at->format('Y-m-d H:i:s'),
                    ]);
                }
            });

            fclose($output);
        }, 'operation-logs-'.now()->format('Ymd-His').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * 构建带筛选条件的查询：关键字（人员/模块/摘要/地址）模糊匹配 + 操作结果过滤
     */
    private function baseQuery(Request $request): Builder
    {
        $search = trim((string) $request->query('search', ''));
        $status = (string) $request->query('status', '');

        return OperationLog::query()
            ->when($search !== '', function (Builder $query) use ($search) {
                $query->where(function (Builder $query) use ($search) {
                    $query->where('username', 'like', "%{$search}%")
                        ->orWhere('module', 'like', "%{$search}%")
                        ->orWhere('summary', 'like', "%{$search}%")
                        ->orWhere('url', 'like', "%{$search}%");
                });
            })
            ->when(in_array($status, self::STATUS_FILTERS, true), function (Builder $query) use ($status) {
                $query->where('status', $status === 'success');
            });
    }

    /**
     * 将日志记录映射为前端输出结构
     *
     * @return array{id: int, username: string, module: string, summary: string, type: string, status: bool, url: string, ip: string, parameters: array<string, mixed>|null, error_message: string|null, cost_ms: int, performed_at: string}
     */
    private function mapLog(OperationLog $log): array
    {
        return [
            'id' => $log->id,
            'username' => $log->username,
            'module' => $log->module,
            'summary' => $log->summary,
            'type' => $log->type,
            'status' => $log->status,
            'url' => $log->url,
            'ip' => $log->ip,
            'parameters' => $log->parameters,
            'error_message' => $log->error_message,
            'cost_ms' => $log->cost_ms,
            'performed_at' => $log->performed_at->format('Y-m-d H:i:s'),
        ];
    }
}
