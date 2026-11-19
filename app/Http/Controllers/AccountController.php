<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Notifications\NewArticleNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function show(Request $request): View
    {
        return view('account.show', [
            'notifications' => $request->user()->notifications()->latest()->paginate(10),
        ]);
    }

    /**
     * Открыть статью из своего уведомления и пометить его как прочитанное.
     * POST + CSRF: переход меняет состояние только по явному действию.
     */
    public function openArticleNotification(Request $request, string $notification): RedirectResponse
    {
        $item = $request->user()->notifications()->findOrFail($notification);
        abort_unless($item->type === NewArticleNotification::class, 404);

        $item->markAsRead();

        $article = Article::query()
            ->whereKey($item->data['article_id'] ?? null)
            ->where('is_published', true)
            ->first();

        if ($article === null) {
            return redirect()->route('news.index')
                ->with('status', 'Эта статья больше недоступна.');
        }

        return redirect()->route('news.show', $article);
    }

    public function readNotifications(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return redirect()->route('account')->with('status', 'Все уведомления отмечены как прочитанные.');
    }
}
