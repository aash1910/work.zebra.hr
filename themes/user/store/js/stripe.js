/*
Exp:resso Store module for ExpressionEngine
Copyright (c) 2010-2019 Exp:resso (support@exp-resso.com)
*/

const stripeElements = [{
    selector: 'number',
    type: 'cardNumber',
    element: null,
}, {
    selector: 'cvv',
    type: 'cardCvc',
    element: null,
}, {
    selector: 'expiry',
    type: 'cardExpiry',
    element: null,
}];

// Custom styling can be passed to options when creating an Element.
// (Note that this demo uses a wider set of styles than the guide below.)
const stripeStyles = {
    base: {
        color: '#32325d',
        fontFamily: '"Helvetica Neue", Helvetica, sans-serif',
        fontSmoothing: 'antialiased',
        fontSize: '16px',
        '::placeholder': {
            color: '#aab7c4'
        }
    },
    invalid: {
        color: '#fa755a',
        iconColor: '#fa755a'
    }
};

(function () {
    let $, lib;

    $ = window.jQuery;

    lib = window.ExpressoStore != null ? window.ExpressoStore : window.ExpressoStore = {};

    // Add Stripe JS library to document head
    lib.adjustHeader = function () {
        // to provide a great user experience for 3D Secure on all devices, you should set your page’s
        // viewport width to device-width with the the viewport meta tag
        $('head').append('<meta name="viewport" content="width=device-width, initial-scale=1" />');
    };

     lib.mountStripeElement = function (publishableApiKey, language) {
        // Create a Stripe client.
        const stripe = Stripe(publishableApiKey);

        // Create an instance of Elements.
        const elements = stripe.elements({
            locale: language,
        });

        // Initial each Stripe element
        stripeElements.forEach(function (stripeElement) {
            // Create an instance of the card Element.
            stripeElement.element = elements.create(stripeElement.type, { style: stripeStyles });

            // Handle real-time validation errors from the card Element.
            stripeElement.element.addEventListener('change', function (event) {
                let displayError = document.getElementById('card-' + stripeElement.selector + '-errors');
                if (event.error) {
                    displayError.textContent = event.error.message;
                } else {
                    displayError.textContent = '';
                }
            });

            stripeElement.element.mount('#card-' + stripeElement.selector + '-element');
        });

        // Handle form submission.
        const form = document.getElementById('payment-form');
        form.addEventListener('submit', function (event) {
            event.preventDefault();
            stripe.createToken(stripeElements[0].element).then(function (result) {
                if (result.error) {
                    // Inform the user if there was an error.
                    let errorElement = document.getElementById('card-errors');
                    errorElement.textContent = result.error.message;
                    return;
                }

                // Send the token to your server.
                stripeTokenHandler(result.token);
            });
        });

        // Submit the form with the token ID.
        function stripeTokenHandler(token) {
            // Insert the token ID into the form so it gets submitted to the server
            let form = document.getElementById('payment-form');
            let hiddenInput = document.createElement('input');
            hiddenInput.setAttribute('type', 'hidden');
            hiddenInput.setAttribute('name', 'payment[token]');
            hiddenInput.setAttribute('value', token.id);
            form.appendChild(hiddenInput);

            // This hidden element is needed to indicate to the backend code that the final step of the checkout
            // process has been executed.
            hiddenInput = document.createElement('input');
            hiddenInput.setAttribute('type', 'hidden');
            hiddenInput.setAttribute('name', 'commit');
            hiddenInput.setAttribute('value', 'Place Order');
            form.appendChild(hiddenInput);

            // Submit the form
            form.submit();
        }
    };

    // initialize
    lib.initStripe = function () {
        lib.adjustHeader();
        lib.mountStripeElement(publishableApiKey, language)
    };

    // register stripe handlers
    $(function () {
        lib.mountStripeElement(publishableApiKey, language)
    });

}).call(jQuery);
