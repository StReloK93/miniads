<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport"
		content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
	<link rel="preload" href="/fonts/InterVariable.woff2" as="font" type="font/woff2" crossorigin />
	<title>Telegram mini app</title>
	<style>
		@font-face {
			font-family: InterVariable;
			font-style: normal;
			font-weight: 100 900;
			font-display: swap;
			src: url("/fonts/InterVariable.woff2") format("woff2");
		}


		#loader {
			position: fixed;
			top: 0;
			left: 0;
			right: 0;
			bottom: 0;
			display: flex;
			justify-content: center;
			align-items: center;
			width: 100vw;
			height: 100vh;
			z-index: 1000;
			background-color: #fff;
		}

		.dot {
			width: 10px;
			height: 10px;
			border: 2px solid #007aff;
			border-radius: 50%;
			float: left;
			margin: 0 5px;
			transform: scale(0);
			-webkit-animation: fx 1000ms ease infinite 0ms;
			animation: fx 1000ms ease infinite 0ms;
		}

		.dot:nth-child(2) {
			-webkit-animation: fx 1000ms ease infinite 300ms;
			animation: fx 1000ms ease infinite 300ms;
		}

		.dot:nth-child(3) {
			-webkit-animation: fx 1000ms ease infinite 600ms;
			animation: fx 1000ms ease infinite 600ms;
		}

		@-webkit-keyframes fx {
			50% {
				transform: scale(1);
				opacity: 1;
			}

			100% {
				opacity: 0;
			}
		}

		@keyframes fx {
			50% {
				transform: scale(1);
				opacity: 1;
			}

			100% {
				opacity: 0;
			}
		}
	</style>
	@vite(['resources/application/app.css', 'resources/application/app.ts'])
</head>

<body>
	<main id="app"></main>
	<div id="loader">
		<div class="dot"></div>
		<div class="dot"></div>
		<div class="dot"></div>
	</div>
</body>

</html>