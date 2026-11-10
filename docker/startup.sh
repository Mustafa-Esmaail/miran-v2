#!/bin/sh

set -e  # Exit if any command fails
#runs from top to bottom only once when container starts
# Run Laravel Artisan commands
echo "Running Laravel Artisan commands and composer update ..."

php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
# php artisan filament:clear-cached-components --no-interaction
php artisan config:cache
# php artisan filament:cache-components --no-interaction
# php artisan storage:link
# php artisan guardian:sync

# php artisan migrate:fresh --force
php artisan migrate --force
# php artisan db:seed --class=RolesSeeder --force
# php artisan db:seed --class=CrmRolePermissionSeeder --force
# php artisan db:seed --class=LmsCmsRolePermissionSeeder --force
# php artisan db:seed --class=PermissionSeeder --force
# php artisan db:seed --class=CouponSeeder --force
# php artisan landing:import-slide-images --apply
# php artisan db:seed --class=LandingPageSeeder --force
# php artisan db:seed --class=CourseProfilePageSeeder --force
# php artisan db:seed --class=CourseGroupInstructorSeeder --force
# php artisan db:seed --class=OrderReportPermissionsSeeder --force
# php artisan db:seed --class=CourseContentDaysSeeder --force
# php artisan db:seed --class=PermissionSeeder --force
# php artisan db:seed --class=CrmModuleSeeder --force
# php artisan db:seed --class=ZoomPermissionSeeder --force
# php artisan db:seed --class=ServiceRequestCategoryPermissionSeeder  --force
# php artisan db:seed --class=SuperAdminSeeder  --force
# php artisan db:seed --class=CrmReportPermissionsSeeder  --force
# php artisan db:seed --class=AssignmentPermissionSeeder --force
# php artisan db:seed --class=BroadcastMailRecipientPermissionsSeeder --force
# php artisan db:seed --class=SendMailPermissionsSeeder --force
# php artisan db:seed --class=StudentImpersonationPermissionSeeder --force
# php artisan db:seed --class=OrderItemUpdatePaidPermissionSeeder  --force
# php artisan db:seed --class=ServiceRequestStatusPermissionsSeeder  --force
# php artisan db:seed --class=EmployeeDailyRequestReportPermissionsSeeder  --force
# php artisan db:seed --class=BankReceiptReplacePermissionSeeder  --force
# php artisan db:seed --class=MarketingSourceReportPermissionSeeder  --force
# php artisan db:seed --class=ServiceRequestStagePermissionsSeeder  --force
# php artisan db:seed --class=ComplaintOpsReportPermissionsSeeder  --force
# php artisan db:seed --class=OrderNoticePermissionSeeder  --force
# php artisan db:seed --class=ServiceRequestAssignPermissionSeeder   --force
# php artisan db:seed --class=InvoicePermissionSeeder   --force
# php artisan db:seed --class=B2bRequestsReportPermissionsSeeder   --force
# php artisan db:seed --class=BootcampSeeder   --force
# php artisan db:seed --class=LandingV2PageSeeder   --force
# php artisan db:seed --class=LandingV3PageSeeder    --force
# php artisan db:seed --class=EmployeeDailyOrderReportPermissionsSeeder --force
# php artisan db:seed --class=ApiClientPermissionSeeder --force

# php artisan db:seed --class=LandingExcelPageSeeder --force
# php artisan db:seed --class=LandingKpiPageSeeder --force
# php artisan db:seed --class=LandingPowerBiPageSeeder --force
# php artisan db:seed --class=LandingVersionPermissionSeeder  --force
php artisan db:seed --class=LandingWebpImagesSeeder --force

# php artisan chat:backfill-rooms

# php artisan service-requests:mark-spam
# php artisan db:seed  --force
# php artisan miran:import-catalog-courses

# php artisan migrate:fresh --force
# php artisan migrate --force
# php artisan db:seed  --force

# php artisan dynamics:sync-warehouses
# php artisan dynamics:sync-categories
# php artisan dynamics:sync-technicians
# php artisan sync:warehouse-stock
# php artisan technicians:sync-stock
# php artisan sync:technician-transfers

echo "Artisan commands executed successfully!"


echo "Fixing storage permissions..."
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache


# Start s6-overlay to manage Nginx & PHP-FPM
exec /init
