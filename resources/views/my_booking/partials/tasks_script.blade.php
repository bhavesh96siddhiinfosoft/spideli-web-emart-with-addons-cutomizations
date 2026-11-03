<script>
    /* ---- Multi-day jobs with tasks: the customer's side (client point 88) --
     *
     * Spec: APP-SPEC-MULTI-DAY-TASKS.md (admin docs). The customer sees the
     * job's tasks and progress, comments on each task (no approval), and pays
     * them: "per_task" - the tasks done and not paid; "end" - every unpaid
     * task once all are done. The amount is re-read on the server
     * (set-task-charge) and paid on the extra-charge payment screen.
     * ------------------------------------------------------------------ */
    var customerTasks = { booking: null, tasks: [], workers: {}, comments: {} };
    var customerTaskText = {
        progress: {!! json_encode(trans('lang.task_progress')) !!},
        total: {!! json_encode(trans('lang.task_total')) !!},
        none: {!! json_encode(trans('lang.task_none_customer')) !!},
        pending: {!! json_encode(trans('lang.task_status_pending')) !!},
        inProgress: {!! json_encode(trans('lang.task_status_in_progress')) !!},
        done: {!! json_encode(trans('lang.task_status_done')) !!},
        paid: {!! json_encode(trans('lang.task_paid')) !!},
        unpaid: {!! json_encode(trans('lang.task_unpaid')) !!},
        comments: {!! json_encode(trans('lang.task_comments')) !!},
        commentAdd: {!! json_encode(trans('lang.task_comment_add')) !!},
        commentPlaceholder: {!! json_encode(trans('lang.task_comment_placeholder')) !!},
        payEnd: {!! json_encode(trans('lang.task_payment_end_info')) !!},
        payPerTask: {!! json_encode(trans('lang.task_payment_per_task_info')) !!},
        payButton: {!! json_encode(trans('lang.pay_tasks')) !!},
        endHint: {!! json_encode(trans('lang.task_pay_end_hint')) !!},
        roles: {
            customer: {!! json_encode(trans('lang.task_role_customer')) !!},
            provider: {!! json_encode(trans('lang.task_role_provider')) !!},
            worker: {!! json_encode(trans('lang.task_role_worker')) !!}
        }
    };
    var CUSTOMER_TASK_PAY_STATUSES = ['Order Accepted', 'Order Assigned', 'Order Ongoing'];

    function customerTaskEscape(text) {
        return String(text == null ? '' : text).replace(/[&<>"']/g, function (ch) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ch];
        });
    }

    function customerTaskMoney(amount) {
        return formatCurrency(parseFloat(amount) || 0, currencyData);
    }

    /* The tasks to pay now - the same rule as the server (App\Support\TaskCharges). */
    function customerTasksPayable(booking, tasks) {
        var unpaid = tasks.filter(function (t) { return t.paid !== true; });
        if (booking.taskPayment === 'per_task') {
            return unpaid.filter(function (t) { return t.status === 'done'; });
        }
        var allDone = tasks.length > 0 && tasks.every(function (t) { return t.status === 'done'; });
        return allDone ? unpaid : [];
    }

    /* Called once the booking is loaded. Shows nothing for an ordinary booking. */
    function initCustomerTasks(booking) {
        if (!booking || booking.hasTasks !== true) {
            return;
        }
        customerTasks.booking = booking;
        $('#booking_tasks').show();
        var ref = database.collection('provider_orders').doc(booking.id);
        ref.onSnapshot(function (snap) {
            if (snap.exists) {
                customerTasks.booking = snap.data();
                renderCustomerTasks();
            }
        });
        ref.collection('tasks').orderBy('order').onSnapshot(function (snap) {
            customerTasks.tasks = snap.docs.map(function (doc) { return Object.assign({ id: doc.id }, doc.data()); });
            customerTasks.tasks.forEach(function (t) {
                if (t.workerId && customerTasks.workers[t.workerId] === undefined) {
                    customerTasks.workers[t.workerId] = '';
                    database.collection('providers_workers').doc(t.workerId).get().then(function (w) {
                        customerTasks.workers[t.workerId] = w.exists ? ((w.data().firstName || '') + ' ' + (w.data().lastName || '')).trim() : '';
                        renderCustomerTasks();
                    }).catch(function () {});
                }
            });
            renderCustomerTasks();
        }, function (err) {
            $('#tasks_error').text(err.message || String(err)).show();
        });
    }

    function renderCustomerTasks() {
        var booking = customerTasks.booking || {};
        var tasks = customerTasks.tasks;
        var done = tasks.filter(function (t) { return t.status === 'done'; }).length;
        var total = tasks.reduce(function (s, t) { return s + (parseFloat(t.price) || 0); }, 0);
        $('#tasks_progress').text(tasks.length ? customerTaskText.progress.replace(':done', done).replace(':count', tasks.length) : '');
        $('#tasks_progress_bar').css('width', (tasks.length ? Math.round(done * 100 / tasks.length) : 0) + '%');
        $('#tasks_payment_mode').text(booking.taskPayment === 'per_task' ? customerTaskText.payPerTask : customerTaskText.payEnd);
        $('#tasks_total').text(tasks.length ? customerTaskText.total.replace(':total', customerTaskMoney(total)) : '');

        var payable = customerTasksPayable(booking, tasks);
        var payAmount = payable.reduce(function (s, t) { return s + (parseFloat(t.price) || 0); }, 0);
        var canPay = CUSTOMER_TASK_PAY_STATUSES.indexOf(booking.status) !== -1 && payAmount > 0;
        $('#pay_tasks_btn').toggle(canPay).text(customerTaskText.payButton.replace(':amount', customerTaskMoney(payAmount)));
        var unpaidLeft = tasks.some(function (t) { return t.paid !== true; });
        $('#tasks_pay_hint').text(booking.taskPayment !== 'per_task' && !canPay && unpaidLeft ? customerTaskText.endHint : '');

        if (!tasks.length) {
            $('#tasks_list').html('<p class="text-muted small">' + customerTaskEscape(customerTaskText.none) + '</p>');
            return;
        }
        var html = '';
        tasks.forEach(function (t) {
            var statusText = t.status === 'done' ? customerTaskText.done : (t.status === 'in_progress' ? customerTaskText.inProgress : customerTaskText.pending);
            var statusClass = t.status === 'done' ? 'badge-success' : (t.status === 'in_progress' ? 'badge-info' : 'badge-secondary');
            var date = t.date && typeof t.date.toDate === 'function' ? t.date.toDate().toLocaleDateString() : '';
            var worker = t.workerId ? customerTasks.workers[t.workerId] : '';
            var files = (Array.isArray(t.files) ? t.files : []).map(function (f) {
                return '<a href="' + customerTaskEscape(f.url) + '" target="_blank" rel="noopener" class="mr-3 small"><i class="fa ' + (f.type === 'image' ? 'fa-image' : 'fa-file') + '"></i> ' + customerTaskEscape(f.name || 'file') + '</a>';
            }).join('');
            html += '<div class="border rounded p-2 mb-2 customer-task" data-id="' + customerTaskEscape(t.id) + '">'
                + '<div class="d-flex flex-wrap align-items-start">'
                + '<div class="mr-2"><span class="font-weight-bold">' + customerTaskEscape(t.title) + '</span>'
                + '<div class="small text-muted">' + customerTaskEscape(date) + (worker ? ' &middot; ' + customerTaskEscape(worker) : '') + '</div></div>'
                + '<div class="ml-auto text-nowrap small"><span class="font-weight-bold">' + customerTaskEscape(customerTaskMoney(t.price)) + '</span> '
                + '<span class="badge ' + statusClass + '">' + customerTaskEscape(statusText) + '</span> '
                + '<span class="badge ' + (t.paid === true ? 'badge-success' : 'badge-light') + '">' + customerTaskEscape(t.paid === true ? customerTaskText.paid : customerTaskText.unpaid) + '</span></div>'
                + '</div>'
                + (t.description ? '<p class="small mt-1 mb-1" style="white-space:pre-line">' + customerTaskEscape(t.description) + '</p>' : '')
                + (files ? '<div>' + files + '</div>' : '')
                + '<button type="button" class="btn btn-sm btn-link px-0 customer-task-comments-toggle">' + customerTaskEscape(customerTaskText.comments) + '</button>'
                + '<div class="customer-task-comments" style="display:none">'
                + '<div class="customer-task-comment-list small"></div>'
                + '<div class="input-group input-group-sm mt-1"><input type="text" class="form-control customer-task-comment-text" maxlength="1000" placeholder="' + customerTaskEscape(customerTaskText.commentPlaceholder) + '">'
                + '<div class="input-group-append"><button type="button" class="btn btn-primary customer-task-comment-add">' + customerTaskEscape(customerTaskText.commentAdd) + '</button></div></div>'
                + '</div></div>';
        });
        $('#tasks_list').html(html);
        Object.keys(customerTasks.comments).forEach(function (taskId) {
            var box = $('.customer-task[data-id="' + taskId + '"]');
            if (box.length) {
                box.find('.customer-task-comments').show();
                renderCustomerTaskComments(taskId);
            }
        });
    }

    $(document).on('click', '.customer-task-comments-toggle', function () {
        var taskId = $(this).closest('.customer-task').data('id');
        var box = $(this).closest('.customer-task').find('.customer-task-comments');
        if (customerTasks.comments[taskId]) {
            customerTasks.comments[taskId].unsubscribe();
            delete customerTasks.comments[taskId];
            box.hide();
            return;
        }
        box.show();
        var entry = { list: [] };
        entry.unsubscribe = database.collection('provider_orders').doc(customerTasks.booking.id).collection('tasks').doc(taskId)
            .collection('comments').orderBy('createdAt').onSnapshot(function (snap) {
                entry.list = snap.docs.map(function (doc) { return doc.data(); });
                renderCustomerTaskComments(taskId);
            });
        customerTasks.comments[taskId] = entry;
    });

    function renderCustomerTaskComments(taskId) {
        var entry = customerTasks.comments[taskId];
        var box = $('.customer-task[data-id="' + taskId + '"] .customer-task-comment-list');
        if (!entry || !box.length) {
            return;
        }
        box.html(entry.list.map(function (c) {
            var when = c.createdAt && typeof c.createdAt.toDate === 'function' ? c.createdAt.toDate().toLocaleString() : '';
            return '<div class="mb-1"><strong>' + customerTaskEscape(customerTaskText.roles[c.role] || c.role || '') + '</strong> '
                + '<span class="text-muted">' + customerTaskEscape(when) + '</span><br>' + customerTaskEscape(c.text) + '</div>';
        }).join(''));
    }

    $(document).on('click', '.customer-task-comment-add', function () {
        var taskBox = $(this).closest('.customer-task');
        var input = taskBox.find('.customer-task-comment-text');
        var text = $.trim(input.val());
        if (!text) {
            return;
        }
        var $btn = $(this).prop('disabled', true);
        database.collection('provider_orders').doc(customerTasks.booking.id).collection('tasks').doc(taskBox.data('id')).collection('comments').add({
            userId: customerTasks.booking.authorID,
            role: 'customer',
            text: text,
            createdAt: firebase.firestore.FieldValue.serverTimestamp()
        }).then(function () {
            input.val('');
        }).catch(function (err) {
            $('#tasks_error').text(err.message || String(err)).show();
        }).then(function () {
            $btn.prop('disabled', false);
        });
    });

    /* Pay: the server re-reads what is owed, then the extra-charge payment screen takes it. */
    $(document).on('click', '#pay_tasks_btn', function () {
        var $btn = $(this).prop('disabled', true);
        $.ajax({
            url: '{{ route('set-task-charge') }}',
            type: 'POST',
            data: { orderId: customerTasks.booking.id },
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (response) {
                if (response && response.success === false) {
                    alert(response.message);
                    $btn.prop('disabled', false);
                    return;
                }
                window.location.href = '{{ route('pay-extra-charge') }}';
            },
            error: function () {
                $btn.prop('disabled', false);
            }
        });
    });
</script>
