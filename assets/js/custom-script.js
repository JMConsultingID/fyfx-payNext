jQuery(document).ready(function() {
  console.log("Tadaaa");
  var expMonthInput = jQuery("#expMonth");
  var expYearInput = jQuery("#expYear");
  var errorContainer = jQuery("#errorContainer");

  if ($('.woocommerce-error').length) {
      console.log("Woo Tadaaa");
      $('html, body').animate({
          scrollTop: 0
      }, 'slow');
  }

  function validateExpirationDate() {
    var today = new Date();
    var currentYear = today.getFullYear() % 100;
    var currentMonth = today.getMonth() + 1;
    var enteredYear = parseInt(expYearInput.val(), 10);
    var enteredMonth = parseInt(expMonthInput.val(), 10);

    errorContainer.text("");

    if (!enteredMonth || !enteredYear) {
      return;
    }

    if (enteredYear < currentYear || (enteredYear === currentYear && enteredMonth < currentMonth)) {
      errorContainer.text("Tanggal kedaluwarsa kartu kredit tidak valid.");
      expMonthInput.addClass("error-text");
      expYearInput.addClass("error-text");
    } else {
      expMonthInput.removeClass("error-text");
      expYearInput.removeClass("error-text");
    }
  }

  expMonthInput.on("focus", function() {
    console.log("Input field bulan (expMonth) mendapatkan fokus.");
  });

  expMonthInput.on("input", function() {
    var cleanedMonth = this.value.replace(/\D/g, "");
    if (cleanedMonth.length >= 2) {
      cleanedMonth = cleanedMonth.slice(0, 2);
      expMonthInput.val(cleanedMonth);
      expYearInput.focus();
    }
    validateExpirationDate();
  });

  expYearInput.on("input", function() {
    var cleanedYear = this.value.replace(/\D/g, "");
    if (cleanedYear.length > 2) {
      cleanedYear = cleanedYear.slice(0, 2);
      expYearInput.val(cleanedYear);
    } else if (cleanedYear.length === 2) {
      expYearInput.blur(); // Unfocus the input field after 2 characters are entered
      validateExpirationDate();
    }
  });
});
