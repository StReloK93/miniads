<!DOCTYPE html>
<html lang="uz">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
	<title>Maxfiylik siyosati va qoidalar — Navoiy e'lonlari</title>
	<meta name="description" content="Navoiy e'lonlari mini ilovasining maxfiylik siyosati va foydalanish qoidalari.">
	<style>
		@font-face {
			font-family: InterVariable;
			font-weight: 100 900;
			font-display: swap;
			src: url("/fonts/InterVariable.woff2") format("woff2");
		}

		:root {
			--bg: #f3f6fb;
			--card: #ffffff;
			--text: #0f172a;
			--muted: #5b6475;
			--border: #e3e8f0;
			color-scheme: light dark;
		}

		@media (prefers-color-scheme: dark) {
			:root {
				--bg: #080b14;
				--card: #111624;
				--text: #e6e9f0;
				--muted: #98a1b3;
				--border: #232a3b;
			}
		}

		* {
			box-sizing: border-box;
		}

		body {
			margin: 0;
			background: var(--bg);
			color: var(--text);
			font: 15px/1.65 InterVariable, system-ui, sans-serif;
		}

		main {
			max-width: 720px;
			margin: 0 auto;
			padding: 28px 16px 48px;
		}

		h1 {
			margin: 0 0 4px;
			font-size: 24px;
			letter-spacing: -0.02em;
		}

		h2 {
			margin: 28px 0 8px;
			font-size: 17px;
		}

		.muted {
			color: var(--muted);
			font-size: 13px;
		}

		section {
			margin-top: 16px;
			border: 1px solid var(--border);
			border-radius: 16px;
			background: var(--card);
			padding: 4px 18px 16px;
		}

		ul {
			padding-left: 20px;
		}

		li+li {
			margin-top: 4px;
		}

		a {
			color: inherit;
		}
	</style>
</head>

<body>
	<main>
		<h1>Maxfiylik siyosati va qoidalar</h1>
		<p class="muted">Navoiy e'lonlari — Telegram mini ilovasi. Oxirgi yangilanish: {{ \Illuminate\Support\Carbon::parse('2026-10-29')->format('d.m.Y') }}</p>

		<section id="privacy">
			<h2>Qanday ma'lumot olinadi</h2>
			<ul>
				<li>Telegram profilingizdan: ID raqam, ism va username (ilovaga kirganingizda Telegram o'zi uzatadi).</li>
				<li>Siz kiritgan ma'lumotlar: e'lon matni, rasmlari, narxi, telefon raqami va tanlagan shahringiz.</li>
				<li>Ilovadan foydalanish: ko'rilgan e'lonlar soni, sevimlilar, kuzatilayotgan qidiruvlar va qidiruv so'rovlari (statistika uchun, shaxsingizga bog'lanmagan holda tahlil qilinadi).</li>
			</ul>

			<h2>Nima uchun ishlatiladi</h2>
			<ul>
				<li>E'lonlaringizni ko'rsatish va xaridorlar siz bilan bog'lana olishi uchun (telefon va username e'londa ko'rinadi).</li>
				<li>Ruxsat bergan bo'lsangiz — bot orqali xabarlar: kuzatuvingizga mos yangi e'lon, e'lon muddati tugashi.</li>
				<li>Firibgarlik va qoidabuzarlikka qarshi moderatsiya.</li>
			</ul>

			<h2>Kimga beriladi</h2>
			<p>Ma'lumotlaringiz sotilmaydi va uchinchi shaxslarga berilmaydi. E'lonlar ilovada va loyihaning Telegram kanal(lar)ida ommaviy ko'rsatiladi.</p>

			<h2>Saqlash va o'chirish</h2>
			<ul>
				<li>E'lonni istalgan vaqtda "E'lonlarim" bo'limidan o'chirishingiz mumkin.</li>
				<li>Bot xabarlarini profil sozlamalarida o'chirish yoki botni to'xtatish mumkin.</li>
				<li>Hisobingiz va barcha ma'lumotlaringizni butunlay o'chirish uchun administratorga yozing.</li>
			</ul>
		</section>

		<section id="rules">
			<h2>E'lon joylash qoidalari</h2>
			<ul>
				<li>Faqat haqiqiy, dolzarb e'lonlar; narx va ma'lumotlar to'g'ri bo'lsin.</li>
				<li>Bir narsani qayta-qayta joylash (spam) mumkin emas — eskisini uzaytiring.</li>
				<li>Taqiqlangan: qurol, giyohvand moddalar, soxta hujjatlar, qonunga zid xizmatlar, kattalar uchun kontent.</li>
				<li>E'lonni to'g'ri bo'limga joylang.</li>
				<li>Qoidabuzar e'lonlar ogohlantirishsiz o'chiriladi, takrorlansa hisob bloklanadi.</li>
			</ul>

			<h2>Javobgarlik</h2>
			<p>Ilova e'lon beruvchi va xaridorni bog'laydi, savdoda vositachi emas. Oldindan pul o'tkazishdan ehtiyot bo'ling, mahsulotni ko'rib oling. Shubhali e'lonni "Shikoyat qilish" tugmasi bilan bildiring.</p>
		</section>

		@if (config('services.telegram.support_username'))
			<p class="muted" style="margin-top: 20px">Savollar bo'yicha: <a href="https://t.me/{{ config('services.telegram.support_username') }}">{{ '@' . config('services.telegram.support_username') }}</a></p>
		@endif
	</main>
</body>

</html>
