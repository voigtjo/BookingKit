(function ($) {
  function showModal() {
    var $m = $('.bkit-modal');
    $m.find('.bkit-feedback').hide().text('');
    $m.show().css('display', 'flex');
  }

  function closeModal() {
    $('.bkit-modal').hide();
  }

  function openModalForOpen(dateISO, datePretty) {
    var $m = $('.bkit-modal');
    $m.find('.title').text('Reservation request');
    $m.find('.bkit-closed-info').hide();
    $m.find('.bkit-res-form').show();
    $m.find('input[name="date"]').val(dateISO);
    $m.find('input[name="date_view"]').val(datePretty);
    showModal();
  }

  function openModalForClosed(dateISO, datePretty, reason) {
    var $m = $('.bkit-modal');
    $m.find('.title').text('Closed');
    $m.find('.bkit-res-form').hide();
    $m.find('.bkit-closed-info').show();
    $m.find('.bkit-closed-date').text(datePretty || '');
    $m.find('.bkit-closed-reason').text(reason ? ('Reason: ' + reason) : '');
    showModal();
  }

  // Offene Tage → Formular
  $(document).on('click', '.bkit-cell.day.open.clickable', function () {
    var date = $(this).data('date'); // YYYY-MM-DD
    var pretty;
    try {
      pretty = new Date(date + 'T00:00:00').toLocaleDateString(undefined, { year: 'numeric', month: 'long', day: 'numeric' });
    } catch (e) {
      pretty = date;
    }
    openModalForOpen(date, pretty);
  });

  // Geschlossene Tage → Info (Reason kommt aus data-reason, kein Ajax)
  $(document).on('click', '.bkit-cell.day.closed', function () {
    var date = $(this).data('date') || '';
    var reason = $(this).data('reason') || '';
    var pretty;
    try {
      pretty = new Date(date + 'T00:00:00').toLocaleDateString(undefined, { year: 'numeric', month: 'long', day: 'numeric' });
    } catch (e) {
      pretty = date;
    }
    openModalForClosed(date, pretty, reason);
  });

  // Schließen
  $(document).on('click', '.bkit-cancel, .bkit-modal .bkit-close', function (e) {
    e.preventDefault();
    closeModal();
  });

  // Formular absenden
  $(document).on('submit', '.bkit-res-form', function (e) {
    e.preventDefault();

    var data = $(this).serializeArray().reduce(function (acc, cur) {
      acc[cur.name] = cur.value;
      return acc;
    }, {});

    data.action = 'bkit_mvp_submit_res';
    data.nonce  = BKIT_MVP.nonce;

    $.post(BKIT_MVP.ajax_url, data, function (resp) {
      var $fb = $('.bkit-feedback');
      if (resp && resp.success) {
        $fb.text(resp.data.msg).css('color', '#2ecc71').show();
        setTimeout(closeModal, 1200);
      } else {
        $fb.text((resp && resp.data && resp.data.msg) || 'Error').css('color', '#e74c3c').show();
      }
    }).fail(function (xhr) {
      var msg = 'Error';
      try { msg = JSON.parse(xhr.responseText).data.msg; } catch (e) {}
      $('.bkit-feedback').text(msg).css('color', '#e74c3c').show();
    });
  });
})(jQuery);
