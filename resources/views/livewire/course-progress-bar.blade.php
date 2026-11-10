<div>
    <div class="flex items-center justify-between text-xs text-muted">
        <span>{{ __('web.my_courses.progress') }}</span>
        <span>{{ max(0, min(100, (int) $progress)) }}%</span>
    </div>
    <div class="mt-2 h-2 w-full rounded-full bg-muted">
        <div class="h-2 rounded-full bg-primary" style="width: {{ max(0, min(100, (int) $progress)) }}%;"></div>
    </div>
</div>
