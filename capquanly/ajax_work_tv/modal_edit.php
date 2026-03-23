<div class="modal fade" id="modalEdit" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title w-100 text-center" id="exampleModalLabel">Công việc</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="post" id="projectFormUpdate" enctype="multipart/form-data">
                <div class="modal-body" id="bodyModalEdit">
                    <?php include('modal_edit_inner.php'); ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-md btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-md btn-info" id="saveButton">Lưu</button>
                </div>
            </form>
            
            <script>
            $(document).ready(function() {
                // Xử lý đóng modal khi nhấn nút đóng
                $('.btn-close, .btn-secondary[data-bs-dismiss="modal"]').on('click', function() {
                    $('#modalEdit').modal('hide');
                });
                
                // Handle form submission
                $("#projectFormUpdate").on('submit', function(e) {
                    e.preventDefault();
                    
                    // Show loading state
                    var $saveBtn = $('#saveButton');
                    var originalBtnText = $saveBtn.html();
                    $saveBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Đang lưu...');
                    
                    // Get form data including files
                    var formData = new FormData(this);
                    
                    // Get the gia_tri_giai_ngan value and ensure it's a number
                    var giaTriGiaiNgan = $('input[name="gia_tri_giai_ngan"]').val() || 0;
                    formData.set('gia_tri_giai_ngan', parseFloat(giaTriGiaiNgan) || 0);
                    
                    // Log form data for debugging
                    var formDataObj = {};
                    formData.forEach(function(value, key) {
                        formDataObj[key] = value;
                    });
                    console.log('Form data being sent:', formDataObj);
                    
                    // Send the form data to the server
                    $.ajax({
                        url: 'ajax_work_tv/update_task.php', // Make sure this is the correct endpoint
                        type: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                // Show success message
                                alert('Cập nhật thành công!');
                                
                                // Close the modal
                                $('#modalEdit').modal('hide');
                                
                                // Reload only the modal content
                                $.get('ajax_work_tv/modal_edit_inner.php', { code: response.data.code || '' }, function(html) {
                                    $('#bodyModalEdit').html(html);
                                });
                                
                                // Optionally, refresh any parent component that needs updating
                                // For example, if you have a task list that needs refreshing:
                                // $('#taskList').load(' #taskList > *');
                                
                            } else {
                                // Show error message
                                alert('Có lỗi xảy ra: ' + (response.message || 'Vui lòng thử lại sau'));
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('AJAX Error:', {
                                status: xhr.status,
                                statusText: xhr.statusText,
                                responseText: xhr.responseText,
                                error: error
                            });
                            
                            var errorMessage = 'Có lỗi xảy ra khi kết nối đến máy chủ. Vui lòng thử lại sau.';
                            
                            try {
                                var response = JSON.parse(xhr.responseText);
                                if (response && response.message) {
                                    errorMessage = response.message;
                                    if (response.debug) {
                                        console.log('Debug info:', response.debug);
                                    }
                                }
                            } catch (e) {
                                console.error('Error parsing error response:', e);
                            }
                            
                            alert(errorMessage);
                        },
                        complete: function() {
                            // Re-enable the save button
                            $saveBtn.prop('disabled', false).html(originalBtnText);
                        }
                    });
                });
            });
            </script>

        </div>
    </div>
</div>
