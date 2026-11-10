<?php

namespace App\Providers;

use App\Repositories\Contracts\BankTransferReceiptRepositoryInterface;
use App\Repositories\Contracts\BlogCategoryRepositoryInterface;
use App\Repositories\Contracts\BlogCommentRepositoryInterface;
use App\Repositories\Contracts\BlogRepositoryInterface;
use App\Repositories\Contracts\CartRepositoryInterface;
use App\Repositories\Contracts\CategoryRepositoryInterface;
use App\Repositories\Contracts\ChatMessageRepositoryInterface;
use App\Repositories\Contracts\ChatRoomRepositoryInterface;
use App\Repositories\Contracts\CommentReplyRepositoryInterface;
use App\Repositories\Contracts\CommentRepositoryInterface;
use App\Repositories\Contracts\CouponRepositoryInterface;
use App\Repositories\Contracts\CourseGroupRepositoryInterface;
use App\Repositories\Contracts\CourseQuestionRepositoryInterface;
use App\Repositories\Contracts\CourseRepositoryInterface;
use App\Repositories\Contracts\CourseReviewRepositoryInterface;
use App\Repositories\Contracts\EnrollmentRepositoryInterface;
use App\Repositories\Contracts\MenuRepositoryInterface;
use App\Repositories\Contracts\OrderRepositoryInterface;
use App\Repositories\Contracts\PageRepositoryInterface;
use App\Repositories\Contracts\PaymentRepositoryInterface;
use App\Repositories\Contracts\QuestionAnswerRepositoryInterface;
use App\Repositories\Contracts\QuestionVoteRepositoryInterface;
use App\Repositories\Contracts\SettingsRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Eloquent\BankTransferReceiptRepository;
use App\Repositories\Eloquent\BlogCategoryRepository;
use App\Repositories\Eloquent\BlogCommentRepository;
use App\Repositories\Eloquent\BlogRepository;
use App\Repositories\Eloquent\CartRepository;
use App\Repositories\Eloquent\CategoryRepository;
use App\Repositories\Eloquent\ChatMessageRepository;
use App\Repositories\Eloquent\ChatRoomRepository;
use App\Repositories\Eloquent\CommentReplyRepository;
use App\Repositories\Eloquent\CommentRepository;
use App\Repositories\Eloquent\CouponRepository;
use App\Repositories\Eloquent\CourseGroupRepository;
use App\Repositories\Eloquent\CourseQuestionRepository;
use App\Repositories\Eloquent\CourseRepository;
use App\Repositories\Eloquent\CourseReviewRepository;
use App\Repositories\Eloquent\EnrollmentRepository;
use App\Repositories\Eloquent\MenuRepository;
use App\Repositories\Eloquent\OrderRepository;
use App\Repositories\Eloquent\PageRepository;
use App\Repositories\Eloquent\PaymentRepository;
use App\Repositories\Eloquent\QuestionAnswerRepository;
use App\Repositories\Eloquent\QuestionVoteRepository;
use App\Repositories\Eloquent\SettingsRepository;
use App\Repositories\Eloquent\UserRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ChatRoomRepositoryInterface::class, ChatRoomRepository::class);
        $this->app->bind(ChatMessageRepositoryInterface::class, ChatMessageRepository::class);
        $this->app->bind(CartRepositoryInterface::class, CartRepository::class);
        $this->app->bind(CategoryRepositoryInterface::class, CategoryRepository::class);
        $this->app->bind(CommentRepositoryInterface::class, CommentRepository::class);
        $this->app->bind(CommentReplyRepositoryInterface::class, CommentReplyRepository::class);
        $this->app->bind(CourseGroupRepositoryInterface::class, CourseGroupRepository::class);
        $this->app->bind(CourseRepositoryInterface::class, CourseRepository::class);
        $this->app->bind(CourseReviewRepositoryInterface::class, CourseReviewRepository::class);
        $this->app->bind(CourseQuestionRepositoryInterface::class, CourseQuestionRepository::class);
        $this->app->bind(CouponRepositoryInterface::class, CouponRepository::class);
        $this->app->bind(EnrollmentRepositoryInterface::class, EnrollmentRepository::class);
        $this->app->bind(OrderRepositoryInterface::class, OrderRepository::class);
        $this->app->bind(PaymentRepositoryInterface::class, PaymentRepository::class);
        $this->app->bind(PageRepositoryInterface::class, PageRepository::class);
        $this->app->bind(BankTransferReceiptRepositoryInterface::class, BankTransferReceiptRepository::class);
        $this->app->bind(BlogRepositoryInterface::class, BlogRepository::class);
        $this->app->bind(BlogCategoryRepositoryInterface::class, BlogCategoryRepository::class);
        $this->app->bind(BlogCommentRepositoryInterface::class, BlogCommentRepository::class);
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->bind(SettingsRepositoryInterface::class, SettingsRepository::class);
        $this->app->bind(MenuRepositoryInterface::class, MenuRepository::class);
        $this->app->bind(QuestionAnswerRepositoryInterface::class, QuestionAnswerRepository::class);
        $this->app->bind(QuestionVoteRepositoryInterface::class, QuestionVoteRepository::class);
    }
}
