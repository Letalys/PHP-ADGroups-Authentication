(function($) {
    // Your connexion button
    $("#loginbtn").click(function() {
        $.ajax({
            type: "POST",
            url: 'phpscripts/auth_process.php', //call the authenticate script
            data: {
                user_username: $("#user_username").val(),
                user_password: $("#user_password").val()
            },
            dataType: 'json', // Type de retour attendu
            success: function(data) {
				console.log(data);
                switch (data.status) {
                    case 'error':
                        $("#errormsg").html("<p>" + data.message + "</p>");
                        break;
                    case 'success':
						//redirect to you page if authorization granted
                        location.assign('index.php');
                        break;
                    default:
                        $("#errormsg").html("<p>Unknown error</p>");
                        break;
                };
            },
            error: function(xhr, status, error) {
               console.log(error); // Put error in browser console for debugging
            }
        });
    });
})(jQuery);

