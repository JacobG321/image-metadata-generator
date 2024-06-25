jQuery(document).ready(function($) {
    // Init Dialog Box
    $('#generate-metadata-dialog').dialog({
        autoOpen: false,
        modal: true,
        buttons: {
            "Generate": {
                text: "Generate",
                id: "generate-metadata-generate-button",
                click: function() {
                    var selectedMetadata = [];
                    $('#generate-metadata-form input:checked').each(function() {
                        selectedMetadata.push($(this).val());
                    });

                    // AJAX Handler
                    $.ajax({
                        url: imageMetadataGenerator.ajax_url,
                        method: 'POST',
                        data: {
                            action: 'generate_metadata',
                            security: imageMetadataGenerator.nonce,
                            metadata: selectedMetadata,
                            attachment_id: imageMetadataGenerator.attachment_id
                        },
                        success: function(response) {
                            if (response.success) {
                                // Display the generated metadata in the dialog box
                                var metadata = response.data.metadata;
                                var resultsHtml = '<p>Generated Metadata:</p><ul>';
                                if (metadata.alt_text) {
                                    resultsHtml += '<li><strong>Alt Text:</strong> ' + metadata.alt_text + ' <button class="keep-metadata" data-key="alt_text">Keep</button></li>';
                                }
                                if (metadata.title) {
                                    resultsHtml += '<li><strong>Title:</strong> ' + metadata.title + ' <button class="keep-metadata" data-key="title">Keep</button></li>';
                                }
                                if (metadata.file_name) {
                                    resultsHtml += '<li><strong>File Name:</strong> ' + metadata.file_name + ' <button class="keep-metadata" data-key="file_name">Keep</button></li>';
                                }
                                if (metadata.caption) {
                                    resultsHtml += '<li><strong>Caption:</strong> ' + metadata.caption + ' <button class="keep-metadata" data-key="caption">Keep</button></li>';
                                }
                                resultsHtml += '</ul>';
                                $('#generate-metadata-results').html(resultsHtml).show();

                                // Attach event listeners to keep buttons
                                $('.keep-metadata').click(function() {
                                    var key = $(this).data('key');
                                    var value = $(this).parent().contents().filter(function() {
                                        return this.nodeType == 3; // Node.TEXT_NODE
                                    }).text().trim();
                                    updateMetadataField(key, value);
                                });

                            } else {
                                alert('Failed to generate metadata: ' + response.data.message);
                            }
                        },
                        error: function() {
                            alert('AJAX request failed.');
                        }
                    });
                },
                disabled: true // Initialize the button as disabled
            },
            "Keep All": {
                text: "Keep All",
                id: "keep-all-metadata",
                click: function() {
                    $('#generate-metadata-results ul li').each(function() {
                        var key = $(this).find('.keep-metadata').data('key');
                        var value = $(this).contents().filter(function() {
                            return this.nodeType == 3; // Node.TEXT_NODE
                        }).text().trim();
                        updateMetadataField(key, value);
                    });
                },
                disabled: false
            },
            "Cancel": function() {
                $(this).dialog("close");
            }
        }
    });

    // Open the dialog box
    $('#generate-metadata-button').click(function(e) {
        e.preventDefault();
        $('#generate-metadata-dialog').dialog('open');
    });

    // Enable/Disable the Generate button based on checkbox selection
    $('#generate-metadata-form input').on('change', function() {
        var isChecked = $('#generate-metadata-form input:checked').length > 0;
        $('#generate-metadata-generate-button').button(isChecked ? 'enable' : 'disable');
    });

    // Select all checkbox option selects all
    $('#all-metadata').click(function() {
        $('#generate-metadata-form input').not(this).prop('checked', this.checked);
        $('#generate-metadata-form input').not(this).trigger('change');
    });

    // If all checkboxes are selected, check the select all checkbox
    $('#generate-metadata-form input').not('#all-metadata').click(function() {
        var totalCheckboxes = $('#generate-metadata-form input').not('#all-metadata').length;
        var checkedCheckboxes = $('#generate-metadata-form input:checked').not('#all-metadata').length;
        $('#all-metadata').prop('checked', totalCheckboxes === checkedCheckboxes);
    });

    function updateMetadataField(key, value) {
        switch (key) {
            case 'alt_text':
                $('#attachment_alt').val(value);
                break;
            case 'title':
                $('#title').val(value);
                break;
            case 'caption':
                $('#attachment_caption').val(value);
                break;
            case 'description':
                $('#attachment_content').val(value);
                break;
        }
    }
});
