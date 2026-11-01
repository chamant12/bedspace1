{{-- Each form (one per currency) totals its own ticked rows --}}
<script>
$(function () {
    function updateTotal(form) {
        var total = 0;
        form.find('.row-check:checked').each(function () { total += parseFloat($(this).data('amount')); });
        form.find('.selected-total').text(total.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
    }
    $('.select-all').on('change', function () {
        var form = $(this).closest('form');
        form.find('.row-check').prop('checked', this.checked);
        updateTotal(form);
    });
    $(document).on('change', '.row-check', function () { updateTotal($(this).closest('form')); });
});
</script>
