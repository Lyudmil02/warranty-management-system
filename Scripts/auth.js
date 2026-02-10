const container = document.querySelector('.container');
    document.querySelector('.register-btn').addEventListener('click', () => container.classList.add('active'));
    document.querySelector('.login-btn').addEventListener('click', () => container.classList.remove('active'));

    
	//<?php if(isset($_POST['register'])): ?> container.classList.add('active'); <?php endif; ?>

	if (isRegisterPost) {
	  container.classList.add('active');
	}

  document.querySelectorAll('.toggle-password').forEach(icon => {
  const input = icon.nextElementSibling;
  const originalIcon = icon.dataset.icon;
  let isVisible = false;

  const isMobile = window.matchMedia("(max-width: 768px)").matches;

  if (!isMobile) {
    icon.addEventListener('mouseenter', () => {
      input.type = 'text';
      icon.classList.replace(originalIcon, 'bx-show');
    });

    icon.addEventListener('mouseleave', () => {
      input.type = 'password';
      icon.classList.replace('bx-show', originalIcon);
    });
  } else {
    icon.addEventListener('click', () => {
      isVisible = !isVisible;

      if (isVisible) {
        input.type = 'text';
        icon.classList.replace(originalIcon, 'bx-show');
      } else {
        input.type = 'password';
        icon.classList.replace('bx-show', originalIcon);
      }
    });
  }
});

    document.getElementById('reloadCaptcha').addEventListener('click', () => {
	  const captchaImage = document.getElementById('captchaImage');
	  captchaImage.src = 'capcha.php?' + Date.now(); 
    });
	
	
	setTimeout(() => {
	  const msg = document.querySelector('.message.success');
	  if (msg) {
		msg.classList.add('hide');
		
		setTimeout(() => {
		  msg.remove();
		  
		  window.location.href = "auth.php";  

		}, 500);
	  }
	}, 3000);
	
	const passField = document.querySelector('.form-box.register input[name="password"]');

	const tLen = document.getElementById('t-len');
	const tUpper = document.getElementById('t-upper');
	const tLower = document.getElementById('t-lower');
	const tDigit = document.getElementById('t-digit');
	const tSpecial = document.getElementById('t-special');

	passField.addEventListener('input', () => {
		const val = passField.value;

		updateTip(tLen, val.length >= 8, "поне 8 символа");
		updateTip(tUpper, /[A-Z]/.test(val), "поне 1 главна буква");
		updateTip(tLower, /[a-z]/.test(val), "поне 1 малка буква");
		updateTip(tDigit, /\d/.test(val), "поне 1 цифра");
		updateTip(tSpecial, /[@$!%*?&.]/.test(val), "поне 1 специален символ");
	});

	function updateTip(el, condition, text) {
		if (condition) {
			el.classList.remove('invalid');
			el.classList.add('valid');
			el.innerHTML = "✔ " + text;
		} else {
			el.classList.remove('valid');
			el.classList.add('invalid');
			el.innerHTML = "✖ " + text;
		}
	}

	const usernameField = document.querySelector('.form-box.register input[name="username"]');

	const uLen   = document.getElementById('u-len');
	const uChars = document.getElementById('u-chars');

	if (usernameField) {
		usernameField.addEventListener('input', () => {
			const val = usernameField.value;

			updateTip(
				uLen,
				val.length >= 5 && val.length <= 20,
				"между 5 и 20 символа"
			);

			updateTip(
				uChars,
				/^[a-zA-Z0-9]*$/.test(val) && val.length > 0,
				"само латински букви и цифри"
			);
		});
	}


const loginTab = document.querySelector('.login-tab');
const registerTab = document.querySelector('.register-tab');

if (loginTab && registerTab) {
  registerTab.addEventListener('click', () => container.classList.add('active'));
  loginTab.addEventListener('click', () => container.classList.remove('active'));
}