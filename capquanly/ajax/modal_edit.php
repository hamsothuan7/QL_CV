<div class="modal" id="modalEdit" >
    <div class="modal-dialog modal-lg" role="document" style="max-width: 1000px;">
        <div class="modal-content">
            <div class="modal-header">
                    <h5 class="modal-title w-100 text-center" id="exampleModalLabel">Dự án</h5>
                    <button type="button" class="close custom-x-btn" data-bs-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <form method="post" id="projectFormUpdate">
                <div class="modal-body" id="bodyModalEdit">
                    <?php include('modal_edit_inner.php'); ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-md btn-secondary" data-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-md btn-info">Lưu</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php include('modal_chonmau.php'); ?>
<style>
                .custom-x-btn {
                    font-size: 2.1rem;
                    font-weight: bold;
                    color: #212529;
                    background: none;
                    border: none;
                    opacity: 0.8;
                    padding: 0 0.75rem;
                    line-height: 1;
                    transition: color 0.2s, opacity 0.2s;
                    box-shadow: none;
                }
                .custom-x-btn:hover {
                    color: #dc3545;
                    opacity: 1;
                    background: none;
                }
                </style>