document.addEventListener('DOMContentLoaded', function () {

  var slider         = document.getElementById('amountSlider');
  var amountDisplay  = document.getElementById('amountDisplay');
  var termDisplay    = document.getElementById('termDisplay');
  var paymentDisplay = document.getElementById('monthlyPayment');
  var termSelect      = document.getElementById('termSelect');

  var amount       = 21500;
  var term         = 3;
  var interestRate = 0.05;

  function calculate() {
    var totalInterest  = amount * interestRate * term;
  var totalRepayment = amount + totalInterest;
  var monthlyPayment = totalRepayment / term;

  var termLabel = term === 1 ? 'Month' : 'Months';

  amountDisplay.innerHTML    = amount.toLocaleString() + ' <span class="unit">PHP</span>';
  termDisplay.innerHTML      = term + ' <span class="unit">' + termLabel + '</span>';
  paymentDisplay.textContent = '₱' + Math.round(monthlyPayment).toLocaleString();
  }

  slider.addEventListener('input', function () {
    amount = Number(slider.value);
    calculate();
  });

  termSelect.addEventListener('change', function () {
     console.time('calc');
  term = Number(this.value);
  calculate();
  console.timeEnd('calc');

  });

  calculate();

});