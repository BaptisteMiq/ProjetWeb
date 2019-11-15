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
        url: "{{ path('event_addPicture') }}",
        type: 'POST',
        data: {
                'link': $("#link").val(),
                'id_Activities': id_Activities
            },
        success: function (data) {
            console.log("Photo envoyée avec succès");
        },
        error : function(jqXHR, textStatus, errorThrown){
            console.log("Impossible d'envoyer la photo");
        }
    });
}

function sendComment(id_Picture, id_Comments) {

    $.ajax({
        url: "{{ path('event_sendComment') }}",
        type: 'POST',
        data: {
                'id_Picture': id_Picture,
                'id_Comments': id_Comments,
                'content': $("#comment").val(),
            },
        success: function (data) {
            console.log("Commentaire envoyé avec succès");
        },
        error : function(jqXHR, textStatus, errorThrown){
            console.log("Impossible d'envoyer le commentaire");
        }
    });
}