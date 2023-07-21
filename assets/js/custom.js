jQuery( document ).ready(function() {
   


jQuery(document).on("keypress change", ".check_creditcard", function (e) {
 jQuery(this).val(function (index, value) {
     
      
    return value.replace(/\W/gi, '').replace(/(.{4})/g, '$1 ');
    
   
  });
  
 
});
 
});