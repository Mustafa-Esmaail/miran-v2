<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolesSeeder::class,
            PermissionSeeder::class,
            ServiceRequestCategoryPermissionSeeder::class,
            ServiceRequestStatusPermissionsSeeder::class,
            ServiceRequestStagePermissionsSeeder::class,
            ServiceRequestAssignPermissionSeeder::class,
            OrderReportPermissionsSeeder::class,
            LmsEnrollmentReportPermissionsSeeder::class,
            MarketingSourceReportPermissionSeeder::class,
            CrmReportPermissionsSeeder::class,
            EmployeeDailyRequestReportPermissionsSeeder::class,
            ComplaintOpsReportPermissionsSeeder::class,
            B2bRequestsReportPermissionsSeeder::class,
            CrmRolePermissionSeeder::class,
            CallQualitySeeder::class,
            LmsCmsRolePermissionSeeder::class,
            CrmSeeder::class,
            ServiceRequestSeeder::class,
            ServiceRequestActivitySeeder::class,
            CrmModuleSeeder::class,
            // SettingsSeeder::class,
            DemoDataSeeder::class,
            BlogSeeder::class,
            CourseQuestionSeeder::class,
            QuestionAnswerSeeder::class,
            QuestionVoteSeeder::class,
            CommentSeeder::class,
            CommentReplySeeder::class,
            AnnouncementSeeder::class,
            NotificationPreferenceSeeder::class,
            CourseContentDaysSeeder::class,
            CouponSeeder::class,
            AssignmentPermissionSeeder::class,
            SendMailPermissionsSeeder::class,
            BroadcastMailRecipientPermissionsSeeder::class,
            StudentImpersonationPermissionSeeder::class,
            OrderItemUpdatePaidPermissionSeeder::class,
            BankReceiptReplacePermissionSeeder::class,
            OrderNoticePermissionSeeder::class,
            InvoicePermissionSeeder::class,
            NelcSyncLogPermissionSeeder::class,
            ApiClientPermissionSeeder::class,
            LandingVersionPermissionSeeder::class,
            SuperAdminSeeder::class,
        ]);
    }
}
