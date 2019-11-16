
function subscribe(id) {
    $.ajax({
        url: "/events/action/subscribe",
        type: 'POST',
        data: {
                'id_Activities': id
            },
        success: function (data) {
            if(data == "OK") {
                location.reload(true);
            } else {
                alert(data);
            }
        }
    });
}

function unSubscribe(id) {
    $.ajax({
        url: "/events/action/unSubscribe",
        type: 'POST',
        data: {
                'id_Activities': id
            },
        success: function (data) {
            if(data == "OK") {
                location.reload(true);
            } else {
                alert(data);
            }
        }
    });
}

function addPicture(id_Activities) {
    $.ajax({
        url: "/events/action/addPicture",
        type: 'POST',
        data: {
                'link': $("#link").val(),
                'id_Activities': id_Activities
            },
        success: function (data) {
            if(data == "OK") {
                location.reload(true);
            } else {
                alert(data);
            }
        }
    });
}

function sendComment(id_Picture) {
    
    $.ajax({
        url: "/events/action/sendcomment",
        type: 'POST',
        data: {
                'id_Picture': id_Picture,
                'content': $("#comment"+ id_Picture).val(),
            },
        success: function (data) {
            if(data == "OK") {
                location.reload(true);
            } else {
                alert(data);
            }
        }
    });
}

function delComment(id) {

    $.ajax({
        url: "/events/action/delcomment",
        type: 'POST',
        data: {
                'id': id
            },
            success: function (data) {
                location.reload(true);    
            }
    });
}