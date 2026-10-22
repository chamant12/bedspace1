<script>
$(function () {
    // Stay dates: no past dates; check-out follows check-in
    $('.bs-date').datepicker({ dateFormat: 'yy-mm-dd', minDate: 0 });
    $('input[name="check_in"].bs-date').on('change', function () {
        var form = $(this).closest('form');
        var checkIn = $.datepicker.parseDate('yy-mm-dd', $(this).val());
        var checkOutField = form.find('input[name="check_out"]');
        var checkOut = checkOutField.val() ? $.datepicker.parseDate('yy-mm-dd', checkOutField.val()) : null;
        if (checkIn && (!checkOut || checkOut <= checkIn)) {
            var next = new Date(checkIn.getTime());
            next.setDate(next.getDate() + 1);
            checkOutField.val($.datepicker.formatDate('yy-mm-dd', next));
        }
    });
});
</script>
