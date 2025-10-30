(function($){
  function openModal(dateISO, reason){
    var $m = $('#bkit-closedday-modal');
    $m.find('input[name="date"]').val(dateISO || '');
    $m.find('input[name="reason"]').val(reason || ''); // <-- als VALUE setzen
    $m.find('.bkit-feedback').hide().text('');
    $m.show().css('display','flex');
  }

  function closeModal(){ $('#bkit-closedday-modal').hide(); }

  // Klick im Admin-Kalender
  $(document).on('click', '.bkit-admin-cal .bkit-cell.day', function(){
    var date   = $(this).data('date') || '';
    var reason = $(this).data('reason') || ''; // sofern im Button hinterlegt
    if(!date) return;
    openModal(String(date), String(reason));
  });

  // Modal schließen
  $(document).on('click', '#bkit-closedday-modal .bkit-close, #bkit-cancel', function(e){
    e.preventDefault();
    closeModal();
  });

  // Speichern
  $(document).on('submit', '#bkit-closedday-form', function(e){
    e.preventDefault();
    var $m  = $('#bkit-closedday-modal');
    var $fb = $m.find('.bkit-feedback');
    var data = {
      action: 'bkit_mvp_save_closed_day',
      nonce:  BKIT_MVP_ADMIN.nonce,
      date:   $(this).find('input[name="date"]').val(),
      reason: $(this).find('input[name="reason"]').val()
    };
    $.post(ajaxurl, data, function(resp){
      if (resp && resp.success){
        $fb.text(resp.data.msg).css('color','#2ecc71').show();
        setTimeout(function(){ window.location.reload(); }, 800);
      } else {
        var msg = (resp && resp.data && resp.data.msg) || 'Error';
        $fb.text(msg).css('color','#e74c3c').show();
      }
    }).fail(function(){
      $fb.text('Error').css('color','#e74c3c').show();
    });
  });
})(jQuery);
