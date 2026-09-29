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
{{--
    For the parcel receipt only. Document 1 asks for a QR code for tracking and
    a barcode that IS the order number, both on the receipt. Loaded from the
    same CDN as jsPDF, and only on the screens that include this partial.
--}}
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jsbarcode/3.11.6/JsBarcode.all.min.js"></script>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
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

        /* The QR code and the barcode, when the screen asked for them. A
         * screen that does not set them - every order screen except parcel -
         * is unaffected.
         *
         * Drawn last so a failure in either cannot cost the customer the rest
         * of the receipt: the figures are already on the page by this point. */
        y = drawReceiptCodes(doc, left, y);

        doc.save(receiptFileName());
    }

    /* Renders the QR code and barcode into the PDF and returns the new y.
     *
     * Both are drawn from a canvas the browser has already rendered, because
     * jsPDF cannot draw either natively. Either one missing, or its library
     * failing to load, is stepped over rather than thrown - a receipt without
     * a barcode is worth more than no receipt.
     */
    function drawReceiptCodes(doc, left, y) {
        var qr = orderReceipt.qrValue ? receiptQrDataUrl(orderReceipt.qrValue) : null;
        var barcode = orderReceipt.barcodeValue ? receiptBarcodeDataUrl(orderReceipt.barcodeValue) : null;

        if (!qr && !barcode) {
            return y;
        }

        y += 24;

        if (qr) {
            doc.addImage(qr, 'PNG', left, y, 90, 90);
            doc.setFontSize(8);
            doc.text("{{ trans('lang.receipt_scan_to_track') }}", left, y + 102);
        }

        if (barcode) {
            /* Beside the QR when both are present, so the receipt stays on one
             * page. The barcode IS the order number, per Document 1, so the
             * number is printed under it for anyone keying it by hand. */
            var barcodeLeft = qr ? left + 120 : left;
            doc.addImage(barcode, 'PNG', barcodeLeft, y, 200, 70);
            doc.setFontSize(8);
            doc.text(String(orderReceipt.barcodeValue), barcodeLeft, y + 82);
        }

        return y + 110;
    }

    function receiptQrDataUrl(value) {
        try {
            if (typeof QRCode === 'undefined') {
                return null;
            }

            /* QRCode writes into an element, so it gets a detached one. */
            var holder = document.createElement('div');
            new QRCode(holder, {
                text: String(value),
                width: 180,
                height: 180,
                correctLevel: QRCode.CorrectLevel.M
            });

            var canvas = holder.querySelector('canvas');
            if (canvas) {
                return canvas.toDataURL('image/png');
            }

            /* Older browsers get an <img> with a data URL instead. */
            var img = holder.querySelector('img');
            return img ? img.src : null;
        } catch (e) {
            console.error('receipt QR code could not be drawn', e);
            return null;
        }
    }

    function receiptBarcodeDataUrl(value) {
        try {
            if (typeof JsBarcode === 'undefined') {
                return null;
            }

            var canvas = document.createElement('canvas');
            JsBarcode(canvas, String(value), {
                format: 'CODE128',
                displayValue: false,
                margin: 0,
                height: 60
            });

            return canvas.toDataURL('image/png');
        } catch (e) {
            /* CODE128 takes anything printable, but a value with a character
             * it cannot encode should not cost the customer their receipt. */
            console.error('receipt barcode could not be drawn', e);
            return null;
        }
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
        /* The order screens show ONE order and fill orderReceipt directly. A
         * list screen - parcel orders - has many, so its button names which
         * one and the page hands back that order's figures. */
        var id = $(this).attr('data-receipt-id');

        if (id && typeof window.resolveOrderReceipt === 'function') {
            orderReceipt = window.resolveOrderReceipt(id);
        }

        downloadOrderReceipt();
    });
</script>
