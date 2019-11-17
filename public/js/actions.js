
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

var dataSet = {{ eventList|json_encode|raw }};

            $(document).ready(function() {
            $('#eventList').DataTable( {
                data: dataSet,
                columns: [
                    // { title: "Id" },
                    { title: "Titre" },
                    { title: "Photo" },
                    { title: "Date début", "data-sort": "%d/%m/%Y" },
                    { title: "Date fin", "data-sort": "%d/%m/%Y" },
                    { title: "Prix" },
                    { title: "Utilisateur" },
                    // { title: "Centre" },
                    { title: "Action" },
                ]
            } );
            } );

            var dataSet3 = {{ pictureList|json_encode|raw }};

            $(document).ready(function() {
            $('#pictureList').DataTable( {
                data: dataSet3,
                columns: [
                    { title: "Activité" },
                    { title: "Photo" },
                    { title: "Auteur Photo" },
                    { title: "Actions" },
                ]
            } );
            } );

              $( function() {

                var res = [];
                dataSet.forEach(dt => {
                    var dtCopy = [...dt];
                    dtCopy.splice(1, 1);
                    dtCopy[4] = dtCopy[4].replace(/<a.+?>/g, '').replace('</a>', '');
                    dtCopy.splice(5, 1);
                    res.push(dtCopy);
                });
                
                var availableTags = [...new Set(res.join().split(','))];
                $("#eventList_wrapper").find('input').autocomplete({
                    source: availableTags,
                    position: {  collision: "flip"  }
                });
            } );

            
            var dataSet2 = {{ commentList|json_encode|raw }};

            $(document).ready(function() {
            $('#commentList').DataTable( {
                data: dataSet2,
                columns: [
                    { title: "Activité" },
                    { title: "Photo" },
                    { title: "Commentaire" },
                    { title: "Auteur Commentaire" },
                    { title: "Actions" },
                ]
            } );
            } );

            function sendMail(id, subject, content) {
            $.ajax({
                url: "/sendmail",
                type: 'POST',
                data: {
                        'id_Rank': id,
                        'subject': subject,
                        'content': content
                    },
                success: function (data) {
                    if(data == "OK") {
                        alert("Mail envoyé!", 'success');
                    } else {
                        alert(data, 'error');
                    }
                }
             });
            }
            
            function delPicture(id) {

                $.ajax({
                    url: "/events/action/delPicture",
                    type: 'POST',
                    data: {
                            'id': id
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


            $('input').trigger("change");
            $('textarea').trigger("change");