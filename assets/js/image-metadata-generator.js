jQuery(document).ready(function ($) {
  // Init Dialog Box
  $("#generate-metadata-dialog").dialog({
      autoOpen: false,
      modal: true,
      buttons: {
          Generate: {
              text: "Generate",
              id: "generate-metadata-generate-button",
              click: generateMetadata,
              disabled: true // Initialize the button as disabled
          },
          "Update All": {
              text: "Update All",
              id: "update-all-metadata",
              disabled: true,
              click: updateAllMetadata
          },
          Cancel: function () {
              $(this).dialog("close");
          }
      }
  });

  // Open the dialog box
  $("#generate-metadata-button").click(function (e) {
      e.preventDefault();
      $("#generate-metadata-dialog").dialog("open");
  });

  // Enable/Disable the Generate button based on checkbox selection
  $("#generate-metadata-form input").on('change', toggleGenerateButton);

  // Select all checkbox option selects all
  $("#all-metadata").click(selectAllCheckboxes);

  // If all checkboxes are selected, check the select all checkbox
  $("#generate-metadata-form input").not("#all-metadata").click(updateSelectAllCheckbox);

  function generateMetadata() {
      var selectedMetadata = [];
      $("#generate-metadata-form input:checked").each(function () {
          selectedMetadata.push($(this).val());
      });

      // AJAX Handler
      $.ajax({
        url: imageMetadataGenerator.ajax_url,
        method: "POST",
        data: {
            action: "generate_metadata",
            security: imageMetadataGenerator.generate_nonce,
            metadata: selectedMetadata,
            attachment_id: imageMetadataGenerator.attachment_id
        },
        success: function (response) {
            if (response.success) {
                $("#generate-metadata-form input").prop("checked", false); // Uncheck all checkboxes
                $("#generate-metadata-generate-button").button("disable");
                $("#update-all-metadata").button("enable");
    
                // Display the generated metadata in the dialog box
                var metadata = response.data.metadata;
                console.log(metadata);
                var resultsHtml = "<p>Generated Metadata:</p><ul>";
                if (metadata.alt) { // Ensure the correct key is used
                    resultsHtml += "<li><strong>Alt Text:</strong> " + metadata.alt + ' <button class="update-metadata" data-key="alt" data-value="' + metadata.alt + '">Update</button></li>';
                }
                if (metadata.title) {
                    resultsHtml += "<li><strong>Title:</strong> " + metadata.title + ' <button class="update-metadata" data-key="title" data-value="' + metadata.title + '">Update</button></li>';
                }
                if (metadata["file-name"]) { // Handle the correct key format
                    resultsHtml += "<li><strong>File Name:</strong> " + metadata["file-name"] + ' <button class="update-metadata" data-key="file_name" data-value="' + metadata["file-name"] + '">Update</button></li>';
                }
                if (metadata.caption) {
                    resultsHtml += "<li><strong>Caption:</strong> " + metadata.caption + ' <button class="update-metadata" data-key="caption" data-value="' + metadata.caption + '">Update</button></li>';
                }
                resultsHtml += "</ul>";
                $("#generate-metadata-results").html(resultsHtml).show();
    
                // Attach event listeners to update buttons
                $(".update-metadata").click(function () {
                    var key = $(this).data("key");
                    var value = $(this).data("value");
                    var metadataToUpdate = {};
                    metadataToUpdate[key] = value;
                    updateMetadataField(metadataToUpdate);
                });
            } else {
                alert("Failed to generate metadata: " + response.data.message);
            }
        },
        error: function () {
            alert("AJAX request failed.");
        }
    });    
  }

  // function updateAllMetadata() {
  //     $("#generate-metadata-results ul li").each(function () {
  //         var key = $(this).find(".update-metadata").data("key");
  //         var value = $(this).find(".update-metadata").data("value");
  //         updateMetadataField(key, value);
  //     });
  // }

  function toggleGenerateButton() {
      var isChecked = $("#generate-metadata-form input:checked").length > 0;
      $("#generate-metadata-generate-button").button(isChecked ? "enable" : "disable");
  }

  function selectAllCheckboxes() {
      $("#generate-metadata-form input").not(this).prop("checked", this.checked);
      $("#generate-metadata-form input").not(this).trigger("change");
  }

  function updateSelectAllCheckbox() {
      var totalCheckboxes = $("#generate-metadata-form input").not("#all-metadata").length;
      var checkedCheckboxes = $("#generate-metadata-form input:checked").not("#all-metadata").length;
      $("#all-metadata").prop("checked", totalCheckboxes === checkedCheckboxes);
  }

  // function updateMetadataField(key, value) {
  //   console.log(key, value);
  //     $.ajax({
  //         url: imageMetadataGenerator.ajax_url,
  //         method: "POST",
  //         data: {
  //             action: "update_metadata",
  //             security: imageMetadataGenerator.update_nonce,
  //             metadata: [key],
  //             attachment_id: imageMetadataGenerator.attachment_id,
  //             [key]: value
  //         },
  //         success: function (response) {
  //             if (response.success) {
  //                 alert("Metadata updated successfully.");
  //             } else {
  //                 alert("Failed to update metadata: " + response.data.message);
  //             }
  //         },
  //         error: function () {
  //             alert("AJAX request failed.");
  //         }
  //     });
  // }


  function updateAllMetadata() {
    var metadataToUpdate = {};
    $("#generate-metadata-results ul li").each(function () {
        var key = $(this).find(".update-metadata").data("key");
        var value = $(this).find(".update-metadata").data("value");
        metadataToUpdate[key] = value;
    });

    console.log(metadataToUpdate);

    updateMetadataField(metadataToUpdate);
}

function updateMetadataField(metadata) {
  console.log(metadata);
    $.ajax({
        url: imageMetadataGenerator.ajax_url,
        method: "POST",
        data: {
            action: "update_metadata",
            security: imageMetadataGenerator.update_nonce,
            metadata: metadata,
            attachment_id: imageMetadataGenerator.attachment_id
        },
        success: function (response) {
            if (response.success) {
                alert("Metadata updated successfully.");
            } else {
                alert("Failed to update metadata: " + response.data.message);
            }
        },
        error: function () {
          console.log(data);
            alert("AJAX request failed.");
        }
    });
}

});
