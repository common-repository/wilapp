// Click Events.
clickEvents = document.getElementsByClassName('wilapp-item');

var loadFunction = function( e ) {
	// AJAX request.
	let cat_id      = this.getAttribute('data-cat-id');
	let service_id  = this.getAttribute('data-service-id');
	let day         = this.getAttribute('data-appointment-weekday');
	let hour        = this.getAttribute('data-appointment-hour');
	let worker      = this.getAttribute('data-worker-id');
	let wizard_step = this.closest('.wizard-fieldset');
	let page        = parseInt( this.closest('.wizard-fieldset').getAttribute('data-page') ) + 1;

	fetch( AjaxVarStep.url, {
		method: 'POST',
		credentials: 'same-origin',
		headers: {
			'Content-Type': 'application/x-www-form-urlencoded',
			'Cache-Control': 'no-cache',
		},
		body: 'action=wizard_step&validate_step_nonce=' + AjaxVarStep.nonce + '&cat_id=' + cat_id + '&service_id=' + service_id + '&day=' + day + '&hour=' + hour + '&worker=' + worker + '&page=' + page,
	})
	.then((resp) => resp.json())
	.then( function(result) {
		if ( result.success && page < 6 ) {
			goToNextPage( e.target, result.data );
		} else if ( page == 6 ) {
			goToSubmitPage( e.target, worker );
		}
	})
	.catch(err => console.log(err));
}

for (var i = 0; i < clickEvents.length; i++) {
	clickEvents[i].addEventListener('click', loadFunction, false);
}

function toggleFieldSet( current ) {
	currentFieldSet = current.closest('.wizard-fieldset');
	currentFieldSet.classList.remove('show');

	nextFieldSet = currentFieldSet.nextSibling;
	nextFieldSet.classList.add('show');
}

function goToNextPage( current, options ) {
	toggleFieldSet( current );
	optionsParent = nextFieldSet.querySelector('.options');

	options.forEach(element => {
		var li = document.createElement('li');
		let name = document.createTextNode(element.name);

		if ( element.image ) {
			let img  = document.createElement('img');
			img.src = element.image;
			img.style.width = "80px";
			li.append(img);
		}
		li.append(name);
		li.className = 'wilapp-item';
		li.setAttribute( 'data-' + element.type, element.id );
		optionsParent.appendChild(li);
	});
	
	for (var i = 0; i < clickEvents.length; i++) {
		clickEvents[i].addEventListener('click', loadFunction, false);
	}
}

function goToSubmitPage( current, worker ) {
	toggleFieldSet( current );
	nextFieldSet.setAttribute( 'data-worker', worker );
}

document.getElementById('wilapp-submit').addEventListener( 'click', ( e ) => {
	let name   = document.getElementById('wilapp-name').value;
	let phone  = document.getElementById('wilapp-phone').value;
	let email  = document.getElementById('wilapp-email').value;
	let notes  = document.getElementById('wilapp-notes').value;
	let worker = e.target.closest('.wizard-fieldset').getAttribute('data-worker');

	// Loader
	loader = document.querySelector('.wilapp-wizard .wilapp-loader');
	loader.style.display = 'block';
	buttonBack = document.getElementById('wilapp-step-back');
	buttonBack.disabled = true;
	buttonSubmit = document.getElementById('wilapp-submit');
	buttonSubmit.disabled = true;

	fetch( AjaxVarSubmit.url, {
		method: 'POST',
		credentials: 'same-origin',
		headers: {
			'Content-Type': 'application/x-www-form-urlencoded',
			'Cache-Control': 'no-cache',
		},
		body: 'action=validate_submit&validate_submit_nonce=' + AjaxVarSubmit.nonce + '&name=' + name + '&phone=' + phone + '&email=' + email + '&notes=' + notes + '&worker_id=' + worker,
	})
	.then((resp) => resp.json())
	.then( function(result) {
		toggleFieldSet( e.target );
		document.getElementById('wilapp-result-appointment').innerHTML = result.data;
		loader.style.display = 'none';
		buttonBack.disabled = false;
		buttonSubmit.disabled = false;
	})
	.catch(err => console.log(err));
});

document.getElementById('wilapp-step-back').addEventListener( 'click', ( e ) => {
	let current = e.target;
	let currentFieldSet = current.closest('.wizard-fieldset');
	currentFieldSet.classList.remove('show');

	nextFieldSet = currentFieldSet.previousSibling;
	nextFieldSet.classList.add('show');
});