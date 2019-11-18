
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

function acceptCookies() {
    $.ajax({
        url: "/cookies",
        type: 'POST',
        data: {},
        success: function (data) {
        location.reload(true);
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
                location.reload(true);
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
                alert(data, 'error');
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

function delEvent(id) {

    $.ajax({
        url: "/events/action/delete",
        type: 'POST',
        data: {
                'id': id
            },
            success: function (data) {
                if(data == 'OK') {
                    location.reload(true);
                } else {
                    alert(data, 'error');
                }   
            }
    });

}

function addToCart(id) {
    $.ajax({
        url: "/shop/products/add",
        type: 'POST',
        data: {
                'id_Product': id,
                'quantity': $('#qt-' + id).val()
            },
        success: function (data) {
            if(data == 'OK') {
                alert("Produit ajouté au panier!", 'success');
                location.href = '/shop/cart';
            } else {
                alert(data, 'error');
            }
        }
    });
}

$('#qt').val(1);
$('input').trigger("change");

function like(id) {
    $.ajax({
        url: "/events/action/like",
        type: 'POST',
        data: {
                'id_Picture': id
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

function unlike(id) {
    $.ajax({
        url: "/events/action/unlike",
        type: 'POST',
        data: {
                'id_Picture': id
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