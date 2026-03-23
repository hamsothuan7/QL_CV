/************************************************************************************
      Sortable functions for Drag & Drop Interface
      *************************************************************************************/
$(function () {
  //Job trạng thái bắt đầu
  $("#new-jobs-list").sortable({
    connectWith: [
      "#in-progress-list",
      "#waiting-jobs-list",
      "#complete-jobs-list",
      "#rework-jobs-list",
    ],
    over: function (event, ui) {
      //triggered when sortable element hovers sortable list

    },
    out: function (event, ui) {
      //event is triggered when a sortable item is moved away from a sortable list.
      // $("#new-jobs").css("background-color", "rgba(0,0,0,.0)");
    },
    receive: function (event, ui) {
      // event is triggered when an item from a connected sortable list has been dropped into another list
      let itemId = ui.item.data('id');
      updateStatus(itemId, 5);
    },
    revert: 100,
    start: function (event, ui) {
      //event is triggered when sorting starts.

    },
    stop: function (event, ui) {
      //event is triggered when sorting has stopped.
    }
  });
});

$(function () {
  //Job trạng thái đang tiến hành
  $("#in-progress-list").sortable({
    connectWith: [
      '#new-jobs-list',
      "#waiting-jobs-list",
      "#complete-jobs-list",
      "#rework-jobs-list",
    ],
    over: function (event, ui) {
      //triggered when sortable element hovers sortable list
      // $("#in-progress").css("background-color", "rgba(0,0,0,.1)");
    },
    out: function (event, ui) {
      //event is triggered when a sortable item is moved away from a sortable list.
      // $("#in-progress").css("background-color", "rgba(0,0,0,.0)");
    },
    receive: function (event, ui) {
      // event is triggered when an item from a connected sortable list has been dropped into another list
      let itemId = ui.item.data('id');
      updateStatus(itemId, 1);
    },
    revert: 100,
    start: function (event, ui) {
      //event is triggered when sorting starts.
      // var elementId = ui.item[0].firstChild.id;
      // $("#" + elementId).css("transform", "rotate(4deg)");
    },
    stop: function (event, ui) {
      let itemId = ui.item.data('id');
      console.log('itemId', itemId);
    }
  });
});

$(function () {
   //Job trạng thái dời
  $("#waiting-jobs-list").sortable({
    connectWith: [
      "#in-progress-list",
      "#complete-jobs-list",
      "#rework-jobs-list",
      "#feedback-jobs-list"
    ],
    over: function (event, ui) {
      //triggered when sortable element hovers sortable list
      // $("#waiting").css("background-color", "rgba(0,0,0,.1)");
    },
    out: function (event, ui) {
      //event is triggered when a sortable item is moved away from a sortable list.
      // $("#waiting").css("background-color", "rgba(0,0,0,.0)");
    },
    receive: function (event, ui) {
      // event is triggered when an item from a connected sortable list has been dropped into another list
      let itemId = ui.item.data('id');
      updateStatus(itemId, 3);
    },
    revert: 100,
    start: function (event, ui) {
      //event is triggered when sorting starts.

    },
    stop: function (event, ui) {
      //event is triggered when sorting has stopped.

    }
  });
});

$(function () {
   //Job trạng thái hoàn thành
  $("#complete-jobs-list").sortable({
    connectWith: [
      "#in-progress-list",
      "#waiting-jobs-list",
      "#rework-jobs-list",
      "#feedback-jobs-list"
    ],
    over: function (event, ui) {
      //triggered when sortable element hovers sortable list
      // $("#complete").css("background-color", "rgba(0,0,0,.1)");
    },
    out: function (event, ui) {
      //event is triggered when a sortable item is moved away from a sortable list.
    },
    receive: function (event, ui) {
      // event is triggered when an item from a connected sortable list has been dropped into another list
      let itemId = ui.item.data('id');
      updateStatus(itemId, 2);
    },
    revert: 100,
    start: function (event, ui) {
      //event is triggered when sorting starts.

    },
    stop: function (event, ui) {
      //event is triggered when sorting has stopped.
      // toastr.error('Không thể thao tác dự án đã hoàn thành');
    }
  });
});

$(function () {
  //Job trạng thái hủy
  $("#rework-jobs-list").sortable({
    connectWith: [
      "#in-progress-list",
      "#waiting-jobs-list",
      "#complete-jobs-list",
    ],
    over: function (event, ui) {
      //triggered when sortable element hovers sortable list
      // $("#rework").css("background-color", "rgba(0,0,0,.1)");
    },
    out: function (event, ui) {
      //event is triggered when a sortable item is moved away from a sortable list.
      // $("#rework").css("background-color", "rgba(0,0,0,.0)");
    },
    receive: function (event, ui) {
      // event is triggered when an item from a connected sortable list has been dropped into another list
      let itemId = ui.item.data('id');
      updateStatus(itemId, 4);
    },
    revert: 100,
    start: function (event, ui) {
    },
    stop: function (event, ui) {
      // toastr.error('Không thể thao tác dự án đã bị hủy');
    }
  });
});

function loadJsDropDrag(){
  $(function () {
    //Job trạng thái bắt đầu
    $("#new-jobs-list").sortable({
      connectWith: [
        "#in-progress-list",
        "#waiting-jobs-list",
        "#complete-jobs-list",
        "#rework-jobs-list",
      ],
      over: function (event, ui) {
        //triggered when sortable element hovers sortable list

      },
      out: function (event, ui) {
        //event is triggered when a sortable item is moved away from a sortable list.
        // $("#new-jobs").css("background-color", "rgba(0,0,0,.0)");
      },
      receive: function (event, ui) {
        // event is triggered when an item from a connected sortable list has been dropped into another list
        let itemId = ui.item.data('id');
        updateStatus(itemId, 5);
      },
      revert: 100,
      start: function (event, ui) {
        //event is triggered when sorting starts.

      },
      stop: function (event, ui) {
        //event is triggered when sorting has stopped.
      }
    });
  });

  $(function () {
    //Job trạng thái đang tiến hành
    $("#in-progress-list").sortable({
      connectWith: [
        '#new-jobs-list',
        "#waiting-jobs-list",
        "#complete-jobs-list",
        "#rework-jobs-list",
      ],
      over: function (event, ui) {
        //triggered when sortable element hovers sortable list
        // $("#in-progress").css("background-color", "rgba(0,0,0,.1)");
      },
      out: function (event, ui) {
        //event is triggered when a sortable item is moved away from a sortable list.
        // $("#in-progress").css("background-color", "rgba(0,0,0,.0)");
      },
      receive: function (event, ui) {
        // event is triggered when an item from a connected sortable list has been dropped into another list
        let itemId = ui.item.data('id');
        updateStatus(itemId, 1);
      },
      revert: 100,
      start: function (event, ui) {
        //event is triggered when sorting starts.
        // var elementId = ui.item[0].firstChild.id;
        // $("#" + elementId).css("transform", "rotate(4deg)");
      },
      stop: function (event, ui) {
        let itemId = ui.item.data('id');
        console.log('itemId', itemId);
      }
    });
  });

  $(function () {
    //Job trạng thái dời
    $("#waiting-jobs-list").sortable({
      connectWith: [
        "#in-progress-list",
        "#complete-jobs-list",
        "#rework-jobs-list",
        "#feedback-jobs-list"
      ],
      over: function (event, ui) {
        //triggered when sortable element hovers sortable list
        // $("#waiting").css("background-color", "rgba(0,0,0,.1)");
      },
      out: function (event, ui) {
        //event is triggered when a sortable item is moved away from a sortable list.
        // $("#waiting").css("background-color", "rgba(0,0,0,.0)");
      },
      receive: function (event, ui) {
        // event is triggered when an item from a connected sortable list has been dropped into another list
        let itemId = ui.item.data('id');
        updateStatus(itemId, 3);
      },
      revert: 100,
      start: function (event, ui) {
        //event is triggered when sorting starts.

      },
      stop: function (event, ui) {
        //event is triggered when sorting has stopped.

      }
    });
  });

  $(function () {
    //Job trạng thái hoàn thành
    $("#complete-jobs-list").sortable({
      connectWith: [
        "#in-progress-list",
        "#waiting-jobs-list",
        "#rework-jobs-list",
        "#feedback-jobs-list"
      ],
      over: function (event, ui) {
        //triggered when sortable element hovers sortable list
        // $("#complete").css("background-color", "rgba(0,0,0,.1)");
      },
      out: function (event, ui) {
        //event is triggered when a sortable item is moved away from a sortable list.
      },
      receive: function (event, ui) {
        // event is triggered when an item from a connected sortable list has been dropped into another list
        let itemId = ui.item.data('id');
        updateStatus(itemId, 2);
      },
      revert: 100,
      start: function (event, ui) {
        //event is triggered when sorting starts.

      },
      stop: function (event, ui) {
        //event is triggered when sorting has stopped.
        // toastr.error('Không thể thao tác dự án đã hoàn thành');
      }
    });
  });

  $(function () {
    //Job trạng thái hủy
    $("#rework-jobs-list").sortable({
      connectWith: [
        "#in-progress-list",
        "#waiting-jobs-list",
        "#complete-jobs-list",
      ],
      over: function (event, ui) {
        //triggered when sortable element hovers sortable list
        // $("#rework").css("background-color", "rgba(0,0,0,.1)");
      },
      out: function (event, ui) {
        //event is triggered when a sortable item is moved away from a sortable list.
        // $("#rework").css("background-color", "rgba(0,0,0,.0)");
      },
      receive: function (event, ui) {
        // event is triggered when an item from a connected sortable list has been dropped into another list
        let itemId = ui.item.data('id');
        updateStatus(itemId, 4);
      },
      revert: 100,
      start: function (event, ui) {
      },
      stop: function (event, ui) {
        // toastr.error('Không thể thao tác dự án đã bị hủy');
      }
    });
  });
}

function updateStatus(code, status){
  $.ajax({
    method: "GET",
    url: "ajax_work/update_status.php",
    data: {
      'code': code,
      'status': status,
    },
    dataType: 'json',
    success: function (res) {
      if (res.status) {
        toastr.success(res.message);
        $('.loading').removeClass('loader');
      } else {
        toastr.error(res.message);
        $('.loading').removeClass('loader');
      }
    }
  });
}