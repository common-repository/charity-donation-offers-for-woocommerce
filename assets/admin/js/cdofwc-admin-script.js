jQuery( function( $ ) {
  'use strict';
  /**
   * Object to handle snappay admin functions.
   */
  
   $(document).ready(function(){
   

    var i = 1;
    var length;

    $("#add").click(function(){

      var tn = $('.rules-items').length;
        tn = parseInt(tn) + 1;
        tn = parseInt(tn) - 1;

        console.log( tn );
      i++;
      $('#charity_dynamic_field').append('<tr class="rules-items" id="row'+tn+'"><td><select id="example-select" name="rule['+tn+'][condition]" required> <option hidden disabled>Please select condition</option><option value="greater">Cart Subtotal Greater Then</option></select><td><input type="number" name="rule['+tn+'][amount]" placeholder="Enter your amount" class="form-control amount_list" required/></td><td><input type="number" name="rule['+tn+'][donation]" placeholder="Enter your donation" class="form-control amount_list" required/></td><td><button type="button" name="remove" id="'+tn+'" class="button-link-delete btn_remove"><span class="dashicons dashicons-no-alt"></span></button></td></tr>');  
    });

    $(document).on('click', '.btn_remove', function(){  
  
    var button_id = $(this).attr("id");     
      $('#row'+button_id+'').remove();  
    });

  });


  $(document).ready(function($) {
      $('#charity-product').select2();
  });


  $('body').on('click','#charity_message_show_enable',function(){
  
    if ($(this).prop('checked')==true){ 
        $('#charity_message_enable_single_product').prop('checked', true);
        $('#charity_message_enable_cart').prop('checked', true);
        $('#charity_message_enable_checkout').prop('checked', true);
        $('input:checkbox').removeAttr('disabled');
        
    }else{
        $('#charity_message_enable_single_product').prop('disabled', true);
        $('#charity_message_enable_cart').prop('disabled', true);
        $('#charity_message_enable_checkout').prop('disabled', true);
        $('input:checkbox').removeAttr('checked');
    }
        
    
  });

});