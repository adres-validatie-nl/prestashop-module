function pollImportProgress() {
    const barFillElement = $('#adresvalidatie-progress-bar-fill');
    const barTextElement = $('#adresvalidatie-progress-bar-text');
    $.ajax({
        url: document.adresvalidatie_async_polling_endpoint,
        type: 'GET',
        success: function (response) {
            if (!response.success) {
                console.log('Import progress polling error: ', response.data);
                return;
            }
            const data = response.data;
            barFillElement.css('width', Math.round(data.rows_processed / data.row_count * 100 * 10)/10 + '%')
            barTextElement.text(data.rows_processed + '/' + (data.row_count-2));

            if (data.async_activity === '') {
                barFillElement.css('width', '100%')
                barTextElement.text('done.');
                return;
            }
            setTimeout(
                function() {
                    pollImportProgress();
                },
                500
            )
        },
        error: function (xhr, status, error) {
            console.log('Import progress polling ajax error: ', error);
        }
    });
}
