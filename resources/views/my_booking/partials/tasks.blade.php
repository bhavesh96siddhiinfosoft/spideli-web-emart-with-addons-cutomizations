{{-- Multi-day jobs with tasks (client point 88, 3 Nov 2026): the customer's
     view of a task job - progress, tasks, files, comments, and paying for
     them. Filled by initCustomerTasks() (my_booking/partials/tasks_script).
     Spec: APP-SPEC-MULTI-DAY-TASKS.md (admin docs). --}}
<div class="{{ $wrapClass ?? 'col-md-12' }} mt-3 mb-2" id="booking_tasks" style="display:none">
    <div class="bg-white p-3">
        <div class="d-flex flex-wrap align-items-center mb-2">
            <h6 class="font-weight-bold mb-0 mr-3">{{ trans('lang.tasks') }}</h6>
            <span class="text-muted small" id="tasks_progress"></span>
            <span class="ml-auto text-muted small" id="tasks_payment_mode"></span>
        </div>
        <div class="progress mb-3" style="height:6px">
            <div class="progress-bar bg-success" id="tasks_progress_bar" style="width:0%"></div>
        </div>
        <div class="alert alert-danger" id="tasks_error" style="display:none"></div>
        <div id="tasks_list"></div>
        <div class="d-flex flex-wrap align-items-center border-top pt-2">
            <span class="text-muted small" id="tasks_total"></span>
            <button type="button" class="btn btn-success ml-auto" id="pay_tasks_btn" style="display:none"></button>
        </div>
        <div class="text-muted small mt-1" id="tasks_pay_hint"></div>
    </div>
</div>
