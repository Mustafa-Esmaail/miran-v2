<?php

namespace App\Livewire\Blog;

use App\Repositories\Contracts\BlogCommentRepositoryInterface;
use App\Services\BlogCommentService;
use App\Services\BlogService;
use App\Support\S3Url;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;
use Livewire\Component;

class BlogDetail extends Component
{
    public string $slug;

    public string $comment = '';

    public function mount(string $slug): void
    {
        $this->slug = $slug;
    }

    public function submitComment(BlogCommentService $commentService, BlogService $blogService): void
    {
        if (! auth()->check()) {
            redirect()->guest(route('login'));

            return;
        }

        $this->validate([
            'comment' => ['required', 'string', 'min:3', 'max:1000'],
        ]);

        $blog = $blogService->findVisibleBySlug($this->slug, auth()->user());
        if (! $blog) {
            abort(404);
        }

        $rateKey = 'blog-comment:'.auth()->id();
        if (RateLimiter::tooManyAttempts($rateKey, 5)) {
            $this->addError('comment', __('web.blog.comment_throttle'));

            return;
        }

        RateLimiter::hit($rateKey, 60);
        $commentService->addComment($blog, auth()->user(), trim($this->comment));

        $this->comment = '';
        $this->dispatch('toast', message: __('web.blog.comment_submitted'));
    }

    public function render(
        BlogService $blogService,
        BlogCommentRepositoryInterface $comments
    ): View {
        $locale = app()->getLocale();
        $blog = $blogService->queryForViewer(auth()->user())
            ->with(['author', 'category'])
            ->where('slug', $this->slug)
            ->first();

        abort_unless($blog, 404);

        $content = $blog->getTranslation('long_description', $locale);
        $imagePath = $blog->image_path;
        $imageUrl = $imagePath ? S3Url::temporary($imagePath) : null;

        $commentsQuery = $comments->forBlog($blog->id)
            ->with('user')
            ->latest();

        if (! $this->canSeeUnapproved()) {
            $commentsQuery->approved();
        }

        return view('livewire.blog.blog-detail', [
            'blog' => $blog,
            'content' => $content,
            'imageUrl' => $imageUrl,
            'comments' => $commentsQuery->get(),
            'showUnapproved' => $this->canSeeUnapproved(),
        ])->layout('layouts.app', ['fullWidth' => true]);
    }

    private function canSeeUnapproved(): bool
    {
        return auth('admin')->check();
    }
}
