var paymentForm = document.querySelector('form.js-payment-form');
var style = {
    base: {
        color: "#32325d",
    }
};


if (paymentForm) {

    var apiKey = paymentForm.getAttribute('data-stripe');
    var clientSecret = paymentForm.getAttribute('data-secret');
    var stripe = Stripe(apiKey);
    var elements = stripe.elements();

    var card = elements.create("card", { style: style });
    card.mount("#card-element");

    var check;

    card.addEventListener('change', ({error}) => {
        const displayError = document.getElementById('card-errors');
        if (error) {
            displayError.textContent = error.message;
        } else {
            displayError.textContent = '';
        }
    });

    paymentForm.addEventListener('submit', function(ev) {
        ev.preventDefault();
        stripe.confirmCardPayment(clientSecret, {
            payment_method: {
                card: card,
            }
        }).then(function(result) {
            if (result.error) {
                console.log(result.error.message);
            } else {
                // The payment has been processed!
                if (result.paymentIntent.status === 'succeeded') {
                    var paymentMethod = document.querySelector('[name="PaymentMethod"]');
                    paymentMethod.value = result.paymentIntent.payment_method;
                    return paymentForm.submit();
                }
            }
        });
    });
}
