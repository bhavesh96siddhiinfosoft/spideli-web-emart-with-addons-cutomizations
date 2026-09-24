{{--
    Order receipt as a downloadable PDF.

    Document 1 point #18. Included by the order detail screens, which each
    hand it the figures they have already worked out - so the receipt and the
    screen can never disagree about a total.

    Uses jsPDF with the autotable plugin, the same pair and the same versions
    the admin panel uses for its reports, loaded from the same CDN. They are
    pulled in here rather than in the shared footer so that the two scripts
    are only fetched on the handful of screens that offer a receipt.
--}}
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.24/jspdf.plugin.autotable.min.js"></script>
<script type="text/javascript">
    /* Everything the receipt needs, filled in by the screen that includes
     * this. Left empty until then so a mis-timed click cannot half-build a
     * receipt out of nothing. */
    var orderReceipt = null;

    /* Money is formatted by the SCREEN, not here, and handed over as text.
     * That keeps one rule about which currency an order reads in - the region
     * it was placed in - rather than a second copy of it living in here. */
    function downloadOrderReceipt() {
        if (!orderReceipt) {
            return;
        }

        if (typeof window.jspdf === 'undefined') {
            /* The CDN did not load. Say so rather than doing nothing, which
             * is indistinguishable from a dead button. */
            alert("{{ trans('lang.receipt_unavailable') }}");
            return;
        }

        var jsPDF = window.jspdf.jsPDF;
        var doc = new jsPDF('p', 'pt', 'a4');
        var left = 40;
        var y = 50;

        /* The screen chooses the heading. A cancelled or rejected order is
         * headed "Order Summary" rather than "Receipt", so a customer cannot
         * hand the PDF over as proof of a purchase that never completed. */
        doc.setFontSize(18);
        doc.text(orderReceipt.title || "{{ trans('lang.receipt_title') }}", left, y);

        y += 26;
        doc.setFontSize(10);

        [
            ["{{ trans('lang.order_number') }}", orderReceipt.orderNumber],
            ["{{ trans('lang.date_created') }}", orderReceipt.date],
            ["{{ trans('lang.status') }}", orderReceipt.status]
        ].forEach(function (row) {
            if (row[1]) {
                doc.text(row[0] + ': ' + row[1], left, y);
                y += 14;
            }
        });

        y += 8;

        if (orderReceipt.storeName) {
            doc.setFont(undefined, 'bold');
            doc.text("{{ trans('lang.receipt_from') }}", left, y);
            doc.setFont(undefined, 'normal');
            y += 14;
            y = writeWrapped(doc, orderReceipt.storeName, left, y);
            if (orderReceipt.storeAddress) {
                y = writeWrapped(doc, orderReceipt.storeAddress, left, y);
            }
            y += 8;
        }

        if (orderReceipt.billingName) {
            doc.setFont(undefined, 'bold');
            doc.text("{{ trans('lang.receipt_to') }}", left, y);
            doc.setFont(undefined, 'normal');
            y += 14;
            y = writeWrapped(doc, orderReceipt.billingName, left, y);
            if (orderReceipt.billingAddress) {
                y = writeWrapped(doc, orderReceipt.billingAddress, left, y);
            }
            y += 8;
        }

        doc.autoTable({
            startY: y + 6,
            head: [[
                "{{ trans('lang.item') }}",
                "{{ trans('lang.quantity') }}",
                "{{ trans('lang.price') }}",
                "{{ trans('lang.total') }}"
            ]],
            body: (orderReceipt.items || []).map(function (item) {
                return [item.name, item.quantity, item.price, item.total];
            }),
            styles: { fontSize: 9, cellPadding: 5 },
            headStyles: { fillColor: [245, 245, 245], textColor: 20 },
            columnStyles: {
                1: { halign: 'right' },
                2: { halign: 'right' },
                3: { halign: 'right' }
            },
            margin: { left: left, right: left }
        });

        y = doc.lastAutoTable.finalY + 20;

        /* The summary lines the screen chose to show, in the screen's order.
         * Anything it left out - a discount that did not apply, a tip that was
         * not given - simply is not in the list. */
        (orderReceipt.lines || []).forEach(function (line) {
            y = summaryLine(doc, line.label, line.value, y, false);
        });

        if (orderReceipt.total) {
            y += 4;
            y = summaryLine(doc, "{{ trans('lang.total') }}", orderReceipt.total, y, true);
        }

        if (orderReceipt.paymentMethod) {
            y += 12;
            doc.setFontSize(10);
            doc.text("{{ trans('lang.payment_methods') }}: " + orderReceipt.paymentMethod, left, y);
        }

        doc.save(receiptFileName());
    }

    /* Right-aligned value against a left label, the way a receipt reads. */
    function summaryLine(doc, label, value, y, bold) {
        var left = 40;
        var right = doc.internal.pageSize.getWidth() - 40;
        doc.setFontSize(bold ? 12 : 10);
        doc.setFont(undefined, bold ? 'bold' : 'normal');
        doc.text(String(label), left, y);
        doc.text(String(value), right, y, { align: 'right' });
        doc.setFont(undefined, 'normal');
        return y + (bold ? 18 : 15);
    }

    /* Long names and addresses wrap rather than running off the page. */
    function writeWrapped(doc, text, left, y) {
        var width = doc.internal.pageSize.getWidth() - (left * 2);
        doc.splitTextToSize(String(text), width).forEach(function (line) {
            doc.text(line, left, y);
            y += 13;
        });
        return y;
    }

    function receiptFileName() {
        var reference = (orderReceipt && orderReceipt.orderNumber)
            ? String(orderReceipt.orderNumber).replace(/[^A-Za-z0-9_-]/g, '')
            : 'order';
        /* Named after whatever the screen called it, so a summary does not
         * arrive in the customer's downloads folder named "receipt". */
        var prefix = ((orderReceipt && orderReceipt.title) || 'receipt')
            .toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
        return prefix + '-' + reference + '.pdf';
    }

    $(document).on('click', '.download-receipt-btn', function () {
        downloadOrderReceipt();
    });
</script>
