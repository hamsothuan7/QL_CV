<div class="modal fade" id="modalMember" >
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title w-100 text-center" id="exampleModalLabel">Thêm thành viên 123</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="post" id="memberFormInsert">
                <div class="modal-body" id="bodyModalMember">
                    <?php include('modal_member_inner.php'); ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-md btn-secondary" data-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-md btn-info">Lưu</button>
                </div>
            </form>

        </div>
    </div>
</div>
