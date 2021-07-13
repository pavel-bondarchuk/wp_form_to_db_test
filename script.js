jQuery(document).ready(function($) {
	
$('#wpfpdb_form').on('submit', function(e) {
e.preventDefault();
var name = jQuery('#name').val();
var email = jQuery('#email').val();
var phone = jQuery('#phone').val();
console.log('ok');
var $form_data = $('#wpfpdb_form').serialize();
$.ajax({
    type: 'POST',
    dataType: 'json',
    url: ajaxurl.url, 
    data: { 
        'action' : 'add_form',
        'name': name,
        'email': email,
        'phone': phone,
    },
    success: function(data){
        if (data.res == true){
            alert(data.message);    // success message
        }else{
            alert(data.message);    // fail
        }
    }
});
});
});