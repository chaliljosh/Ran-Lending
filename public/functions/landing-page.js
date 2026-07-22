document.addEventListener('DOMContentLoaded', function () {

  var slider         = document.getElementById('amountSlider');
  var amountDisplay  = document.getElementById('amountDisplay');
  var termDisplay    = document.getElementById('termDisplay');
  var paymentDisplay = document.getElementById('monthlyPayment');
  var termButtons    = document.querySelectorAll('.term-pills button');

  var amount       = 21500;
  var term         = 3;
  var interestRate = 0.05;

  function calculate() {
    var totalInterest  = amount * interestRate * term;
    var totalRepayment = amount + totalInterest;
    var monthlyPayment = totalRepayment / term;

    amountDisplay.innerHTML    = amount.toLocaleString() + ' <span class="unit">PHP</span>';
    termDisplay.innerHTML      = term + ' <span class="unit">Month</span>';
    paymentDisplay.textContent = '₱' + Math.round(monthlyPayment).toLocaleString();
  }

  slider.addEventListener('input', function () {
    amount = Number(slider.value);
    calculate();
  });

  termButtons.forEach(function (button) {
    button.addEventListener('click', function () {
      termButtons.forEach(function (btn) { btn.classList.remove('active'); });
      this.classList.add('active');
      term = Number(this.getAttribute('data-term'));
      calculate();
    });
  });

  calculate();

});