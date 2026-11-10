<?php

namespace App\Providers;

use App\Models\Announcement;
use App\Models\Assignment;
use App\Models\AssignmentRule;
use App\Models\AssignmentSubmission;
use App\Models\BankTransferReceipt;
use App\Models\Blog;
use App\Models\BlogCategory;
use App\Models\BlogComment;
use App\Models\BroadcastMail;
use App\Models\BroadcastMailRecipient;
use App\Models\Bundle;
use App\Models\CallEvaluation;
use App\Models\CallQualityCriterion;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Certificate;
use App\Models\CertificateRequest;
use App\Models\ChatMessage;
use App\Models\ChatMessageAction;
use App\Models\ChatRoomParticipant;
use App\Models\Comment;
use App\Models\CommentReply;
use App\Models\CommissionLedger;
use App\Models\Company;
use App\Models\ContentProgress;
use App\Models\Country;
use App\Models\Coupon;
use App\Models\CouponRedemption;
use App\Models\CouponTarget;
use App\Models\Course;
use App\Models\CourseAttachment;
use App\Models\CourseCurriculumSection;
use App\Models\CourseGroup;
use App\Models\CourseGroupSession;
use App\Models\CourseQuestion;
use App\Models\CourseReview;
use App\Models\CrmActivity;
use App\Models\CrmTask;
use App\Models\Deal;
use App\Models\Department;
use App\Models\Discount;
use App\Models\Employee;
use App\Models\Enrollment;
use App\Models\ImpersonationLog;
use App\Models\Invoice;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\NotificationPreference;
use App\Models\Order;
use App\Models\OrderAttribution;
use App\Models\OrderItem;
use App\Models\OrderItemSeat;
use App\Models\Page;
use App\Models\Payment;
use App\Models\Qualification;
use App\Models\QuestionAnswer;
use App\Models\QuestionVote;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizOption;
use App\Models\QuizQuestion;
use App\Models\Quote;
use App\Models\SalesTarget;
use App\Models\Section;
use App\Models\SectionField;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestActivity;
use App\Models\ServiceRequestType;
use App\Models\Setting;
use App\Models\ShareLink;
use App\Models\Team;
use App\Models\User;
use App\Models\WorkflowRule;
use App\Models\ZoomSessionAttendance;
use App\Policies\AnnouncementPolicy;
use App\Policies\AssignmentPolicy;
use App\Policies\AssignmentRulePolicy;
use App\Policies\AssignmentSubmissionPolicy;
use App\Policies\BankTransferReceiptPolicy;
use App\Policies\BlogCategoryPolicy;
use App\Policies\BlogCommentPolicy;
use App\Policies\BlogPolicy;
use App\Policies\BroadcastMailPolicy;
use App\Policies\BroadcastMailRecipientPolicy;
use App\Policies\BundlePolicy;
use App\Policies\CallEvaluationPolicy;
use App\Policies\CallQualityCriterionPolicy;
use App\Policies\CartItemPolicy;
use App\Policies\CartPolicy;
use App\Policies\CategoryPolicy;
use App\Policies\CertificatePolicy;
use App\Policies\CertificateRequestPolicy;
use App\Policies\ChatMessageActionPolicy;
use App\Policies\ChatMessagePolicy;
use App\Policies\ChatRoomParticipantPolicy;
use App\Policies\CommentPolicy;
use App\Policies\CommentReplyPolicy;
use App\Policies\CommissionLedgerPolicy;
use App\Policies\CompanyPolicy;
use App\Policies\ContentProgressPolicy;
use App\Policies\CountryPolicy;
use App\Policies\CouponPolicy;
use App\Policies\CouponRedemptionPolicy;
use App\Policies\CouponTargetPolicy;
use App\Policies\CourseAttachmentPolicy;
use App\Policies\CourseCurriculumSectionPolicy;
use App\Policies\CourseGroupPolicy;
use App\Policies\CourseGroupSessionPolicy;
use App\Policies\CoursePolicy;
use App\Policies\CourseQuestionPolicy;
use App\Policies\CourseReviewPolicy;
use App\Policies\CrmActivityPolicy;
use App\Policies\CrmTaskPolicy;
use App\Policies\DatabaseNotificationPolicy;
use App\Policies\DealPolicy;
use App\Policies\DepartmentPolicy;
use App\Policies\DiscountPolicy;
use App\Policies\EmployeePolicy;
use App\Policies\EnrollmentPolicy;
use App\Policies\ImpersonationLogPolicy;
use App\Policies\InvoicePolicy;
use App\Policies\LessonPolicy;
use App\Policies\LessonProgressPolicy;
use App\Policies\MenuItemPolicy;
use App\Policies\MenuPolicy;
use App\Policies\NotificationPreferencePolicy;
use App\Policies\OrderAttributionPolicy;
use App\Policies\OrderItemPolicy;
use App\Policies\OrderItemSeatPolicy;
use App\Policies\OrderPolicy;
use App\Policies\PagePolicy;
use App\Policies\PaymentPolicy;
use App\Policies\PermissionPolicy;
use App\Policies\QualificationPolicy;
use App\Policies\QuestionAnswerPolicy;
use App\Policies\QuestionVotePolicy;
use App\Policies\QuizAttemptPolicy;
use App\Policies\QuizOptionPolicy;
use App\Policies\QuizPolicy;
use App\Policies\QuizQuestionPolicy;
use App\Policies\QuotePolicy;
use App\Policies\RolePolicy;
use App\Policies\SalesTargetPolicy;
use App\Policies\SectionFieldPolicy;
use App\Policies\SectionPolicy;
use App\Policies\ServiceRequestActivityPolicy;
use App\Policies\ServiceRequestPolicy;
use App\Policies\ServiceRequestTypePolicy;
use App\Policies\SettingPolicy;
use App\Policies\ShareLinkPolicy;
use App\Policies\TeamPolicy;
use App\Policies\UserPolicy;
use App\Policies\WorkflowRulePolicy;
use App\Policies\ZoomSessionAttendancePolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Notifications\DatabaseNotification;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Announcement::class => AnnouncementPolicy::class,
        Assignment::class => AssignmentPolicy::class,
        AssignmentRule::class => AssignmentRulePolicy::class,
        AssignmentSubmission::class => AssignmentSubmissionPolicy::class,
        BankTransferReceipt::class => BankTransferReceiptPolicy::class,
        CallEvaluation::class => CallEvaluationPolicy::class,
        CallQualityCriterion::class => CallQualityCriterionPolicy::class,
        Blog::class => BlogPolicy::class,
        BlogCategory::class => BlogCategoryPolicy::class,
        BlogComment::class => BlogCommentPolicy::class,
        BroadcastMail::class => BroadcastMailPolicy::class,
        BroadcastMailRecipient::class => BroadcastMailRecipientPolicy::class,
        Bundle::class => BundlePolicy::class,
        Cart::class => CartPolicy::class,
        CartItem::class => CartItemPolicy::class,
        Category::class => CategoryPolicy::class,
        Certificate::class => CertificatePolicy::class,
        CertificateRequest::class => CertificateRequestPolicy::class,
        ChatMessage::class => ChatMessagePolicy::class,
        ChatMessageAction::class => ChatMessageActionPolicy::class,
        ChatRoomParticipant::class => ChatRoomParticipantPolicy::class,
        Comment::class => CommentPolicy::class,
        CommentReply::class => CommentReplyPolicy::class,
        CommissionLedger::class => CommissionLedgerPolicy::class,
        Company::class => CompanyPolicy::class,
        ContentProgress::class => ContentProgressPolicy::class,
        Country::class => CountryPolicy::class,
        Coupon::class => CouponPolicy::class,
        CouponRedemption::class => CouponRedemptionPolicy::class,
        CouponTarget::class => CouponTargetPolicy::class,
        Course::class => CoursePolicy::class,
        CourseAttachment::class => CourseAttachmentPolicy::class,
        CourseCurriculumSection::class => CourseCurriculumSectionPolicy::class,
        CourseGroup::class => CourseGroupPolicy::class,
        CourseGroupSession::class => CourseGroupSessionPolicy::class,
        CourseQuestion::class => CourseQuestionPolicy::class,
        CourseReview::class => CourseReviewPolicy::class,
        CrmActivity::class => CrmActivityPolicy::class,
        CrmTask::class => CrmTaskPolicy::class,
        DatabaseNotification::class => DatabaseNotificationPolicy::class,
        Deal::class => DealPolicy::class,
        Discount::class => DiscountPolicy::class,
        Department::class => DepartmentPolicy::class,
        Employee::class => EmployeePolicy::class,
        Enrollment::class => EnrollmentPolicy::class,
        ImpersonationLog::class => ImpersonationLogPolicy::class,
        Invoice::class => InvoicePolicy::class,
        Lesson::class => LessonPolicy::class,
        LessonProgress::class => LessonProgressPolicy::class,
        Menu::class => MenuPolicy::class,
        MenuItem::class => MenuItemPolicy::class,
        NotificationPreference::class => NotificationPreferencePolicy::class,
        Order::class => OrderPolicy::class,
        OrderAttribution::class => OrderAttributionPolicy::class,
        OrderItem::class => OrderItemPolicy::class,
        OrderItemSeat::class => OrderItemSeatPolicy::class,
        Page::class => PagePolicy::class,
        Payment::class => PaymentPolicy::class,
        Qualification::class => QualificationPolicy::class,
        Permission::class => PermissionPolicy::class,
        QuestionAnswer::class => QuestionAnswerPolicy::class,
        QuestionVote::class => QuestionVotePolicy::class,
        Quiz::class => QuizPolicy::class,
        QuizAttempt::class => QuizAttemptPolicy::class,
        QuizOption::class => QuizOptionPolicy::class,
        QuizQuestion::class => QuizQuestionPolicy::class,
        Quote::class => QuotePolicy::class,
        Role::class => RolePolicy::class,
        SalesTarget::class => SalesTargetPolicy::class,
        SectionField::class => SectionFieldPolicy::class,
        Section::class => SectionPolicy::class,
        ServiceRequest::class => ServiceRequestPolicy::class,
        ServiceRequestActivity::class => ServiceRequestActivityPolicy::class,
        ServiceRequestType::class => ServiceRequestTypePolicy::class,
        Setting::class => SettingPolicy::class,
        ShareLink::class => ShareLinkPolicy::class,
        Team::class => TeamPolicy::class,
        User::class => UserPolicy::class,
        WorkflowRule::class => WorkflowRulePolicy::class,
        ZoomSessionAttendance::class => ZoomSessionAttendancePolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}
