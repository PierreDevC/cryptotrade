<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>CryptoTrade</title>
	<!-- CDN Imports -->
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-SgOJa3DmI69IUzQ2PVdRZhwQ+dy64/BUtbMJw1MZ8t5HZApcHrRKUc4W0kG879m7" crossorigin="anonymous">
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js" integrity="sha384-k6d4wzSIapyDyv1kpU366/PK5hCdSbCRGRCMv+eplOQJWyd1fbcAu9OCUj5zNLiq" crossorigin="anonymous"></script>
	<script src="https://kit.fontawesome.com/f09d5e5c92.js" crossorigin="anonymous"></script>
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css"/>
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.3.0/chart.umd.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Lora:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
	<!-- CSS - Use root-relative path -->
	<link rel="stylesheet" href="/cryptotrade/css/style.css">
</head>
<body>
	<!-- Navbar --->
<nav class="sticky-top navbar navbar-expand-lg py-4 border-bottom" style="background-color: rgba(255, 255, 255, 0.8);">
<div class="container">
    <a class="navbar-brand brand-logo" href="/cryptotrade/">CryptoTrade</a>
<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarSupportedContent">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item"><a class="nav-link active" aria-current="page" href="#">Ce qu'on offre</a></li>
		<li class="nav-item"><a class="nav-link active" aria-current="page" href="#">Services</a></li>
		<li class="nav-item"><a class="nav-link active" aria-current="page" href="#section-5">Cryptomonnaies</a></li>
		<li class="nav-item"><a class="nav-link active" aria-current="page" href="#">À propos</a></li>
      </ul>
<div class="d-flex gap-2">
    <a href="/cryptotrade/signup" class="button-57 text-decoration-none" role="button"><span class="text">Commencer gratuitement</span><span>Créer un compte</span></a>
    <a href="/cryptotrade/login" class="action-button text-decoration-none">Se connecter</a>
</div>
    </div>
  </div>
</nav>
	<!-- Section 1 : Banner -->
	<section id="section1">
		<div class="container-fluid p-0"> <div class="position-relative">
		<img src="/cryptotrade/img/banner.png" class="w-100" style="height: 500px; object-fit: cover;" alt="Banner background">
		<div class="position-absolute top-50 start-50 translate-middle text-center mt-n5">
            <h1 class="display-4 mb-4">La meilleure plateforme pour investir dans les meilleures cryptos</h1>
            <p class="bg-white bg-opacity-75 shadow p-3 rounded">Découvrez CryptoTrade, votre plateforme intuitive et sécurisée...</p>
            <div class="d-flex justify-content-center gap-3">
                <a href="/cryptotrade/signup" class="button-57 text-decoration-none" role="button"><span class="text">Commencer gratuitement</span><span>Créer un compte</span></a>
                <a href="/cryptotrade/login" class="action-button text-decoration-none">Se connecter</a>
            </div>
        </div>
	    </div></div>
	</section>

	<!-- Section 2 : SwiperJS -->
	<section id="section2">
	    <h1 class="text-center">Découvrez notre plateforme complète</h1>
		<div class="swiper-container"> <div class="swiper mySwiper">
			<div class="swiper-wrapper">
			  <div class="swiper-slide"><img src="/cryptotrade/img/swiper-1.png" alt="Image 1"/></div>
			  <div class="swiper-slide"><img src="/cryptotrade/img/swiper-2.png" alt="Image 2"/></div>
			  <div class="swiper-slide"><img src="/cryptotrade/img/swiper-3.png" alt="Image 3"/></div>
			</div>
			<div class="swiper-button-next"></div><div class="swiper-button-prev"></div><div class="swiper-pagination"></div>
			<div class="autoplay-progress"><svg viewBox="0 0 48 48"><circle cx="24" cy="24" r="20"></circle></svg><span></span></div>
		</div></div>
	</section>

	<!-- Section 3 : 2 columns -->
	<section id="section3-2columns">
		<div class="container text-center"> <div class="row align-items-center">
            <div class="col"><img class="custom-img" src="/cryptotrade/img/iphone.png" width="500px" alt=""/></div>
            <div class="col text-start">
              <h1 class="text text-start">Plateforme performante et sécurisée</h1>
              <p class="text-start">Notre plateforme CryptoTrade se distingue par son infrastructure robuste...</p>
              <a href="/cryptotrade/signup" class="button-57 text-decoration-none" role="button"><span class="text">Découvrir la plateforme</span><span>Créer un compte</span></a>
            </div>
  		</div></div>
	</section>

	<!-- Section 3 : cards showing services -->
    <section id="section-3">
        <h1 class="text-center">Explorez nos services et fonctionalités</h1>
        <div class="container"> <div class="card-group">
            <div class="card"> <img src="/cryptotrade/img/simple.png" class="card-img-top" alt="...">
                <div class="card-body"> <h5 class="card-title">Trading Simplifié</h5><p class="card-text">Accédez facilement...</p><button class="action-button" type="button">En savoir plus</button></div>
            </div>
            <div class="card"> <img src="/cryptotrade/img/reel.png" class="card-img-top" alt="...">
                 <div class="card-body"> <h5 class="card-title">Suivi en Temps Réel</h5><p class="card-text">Surveillez vos investissements...</p><button class="action-button" type="button">En savoir plus</button></div>
            </div>
            <div class="card"> <img src="/cryptotrade/img/securite.png" class="card-img-top" alt="...">
                 <div class="card-body"> <h5 class="card-title">Sécurité Maximale</h5><p class="card-text">Profitez d'une protection...</p><button class="action-button" type="button">En savoir plus</button></div>
            </div>
        </div></div>
	</section>

	<!-- Section4 -->
	<section id="section-4">
		<div class="container text-center"> <div class="row align-items-center">
            <div class="col text-end">
                <h1 class="text text-end">L'innovation au service de votre réussite</h1>
                <p class="text-end">Découvrez une plateforme en constante évolution...</p>
                <a href="/cryptotrade/signup" class="button-57 text-decoration-none" role="button"><span class="text">Découvrir la plateforme</span><span>Créer un compte</span></a>
            </div>
            <div class="col"><img class="custom-img" src="/cryptotrade/img/eth.jpg" width="500px" alt=""/></div>
  		</div></div>
	</section>

	<!-- Section 5 : Static Table -->
	<section id="section-5">
	    <div class="container">
		<h1 class="text text-center">Aperçu du marché de cryptomonnaies</h1>
		<p class="text-center">Suivez en temps réel l'évolution des principales cryptomonnaies...</p>
        <table class="table table-hover table-striped table-bordered crypto-table" style="border-radius: 8px; overflow: hidden;"><thead class="table-dark"><tr><th>#</th><th>Name</th><th>Symbol</th><th>Price (USD)</th><th>24h Change</th><th>Market Cap</th><th>Volume (24h)</th></tr></thead><tbody>
            <tr><td>1</td><td><img src="https://assets.coingecko.com/coins/images/1/thumb/bitcoin.png" width="20" class="me-2">Bitcoin</td><td>BTC</td><td>$67,432.89</td><td class="text-success">+2.45%</td><td>$1.32T</td><td>$28.4B</td></tr>
            <tr><td>2</td><td><img src="https://assets.coingecko.com/coins/images/279/thumb/ethereum.png" width="20" class="me-2">Ethereum</td><td>ETH</td><td>$3,284.56</td><td class="text-success">+1.82%</td><td>$394.5B</td><td>$12.1B</td></tr>
            <tr><td>3</td><td><img src="https://assets.coingecko.com/coins/images/325/thumb/Tether.png" width="20" class="me-2">Tether</td><td>USDT</td><td>$1.00</td><td class="text-success">+0.01%</td><td>$83.2B</td><td>$62.4B</td></tr>
            <tr><td>4</td><td><img src="https://assets.coingecko.com/coins/images/825/thumb/bnb-icon2_2x.png" width="20" class="me-2">BNB</td><td>BNB</td><td>$432.78</td><td class="text-danger">-1.23%</td><td>$66.8B</td><td>$1.8B</td></tr>
            <tr><td>5</td><td><img src="https://assets.coingecko.com/coins/images/44/thumb/xrp-symbol-white-128.png" width="20" class="me-2">XRP</td><td>XRP</td><td>$0.5423</td><td class="text-success">+3.67%</td><td>$28.9B</td><td>$1.2B</td></tr>
        </tbody></table>
		</div>
	</section>

	<!-- Footer -->
	<section class="footer"> <footer class="bg-light text-muted pt-5 mt-5">
    <div class="container text-center text-md-start"> <hr class="mb-4"> <div class="row mt-3">
        <div class="col-md-3 col-lg-4 col-xl-3 mx-auto mb-4"> <h6 class="text fw-bold mb-4 brand-logo">CryptoTrade</h6><p>Votre plateforme de confiance...</p></div>
        <div class="col-md-2 col-lg-2 col-xl-2 mx-auto mb-4"> <h6 class="text-uppercase fw-bold mb-4">Services</h6><p><a href="#!" class="text-reset">Acheter/Vendre</a></p><p><a href="#section-5" class="text-reset">Marchés</a></p><p><a href="#!" class="text-reset">Portefeuille</a></p><p><a href="#!" class="text-reset">Sécurité</a></p></div>
        <div class="col-md-3 col-lg-2 col-xl-2 mx-auto mb-4"> <h6 class="text-uppercase fw-bold mb-4">Liens Utiles</h6><p><a href="#!" class="text-reset">Centre d'aide</a></p><p><a href="#!" class="text-reset">Frais</a></p><p><a href="#!" class="text-reset">À Propos</a></p><p><a href="#!" class="text-reset">Termes et Conditions</a></p><p><a href="#!" class="text-reset">Politique de Confidentialité</a></p></div>
        <div class="col-md-4 col-lg-3 col-xl-3 mx-auto mb-md-0 mb-4"> <h6 class="text-uppercase fw-bold mb-4">Contact</h6><p><i class="fas fa-home me-3"></i> Montréal, QC...</p><p><i class="fas fa-envelope me-3"></i> info@cryptotrade.example.com</p><p><i class="fas fa-phone me-3"></i> + 1 514 123 4567</p><div class="mt-3"><a href="#!" class="me-4 text-reset"><i class="fab fa-facebook-f"></i></a><a href="#!" class="me-4 text-reset"><i class="fab fa-twitter"></i></a><a href="#!" class="me-4 text-reset"><i class="fab fa-linkedin"></i></a><a href="#!" class="me-4 text-reset"><i class="fab fa-github"></i></a></div></div>
    </div></div>
	<div class="text-center p-4" style="background-color: rgba(0, 0, 0, 0.05);">© 2025 Copyright: <a class="text-reset fw-bold" href="/cryptotrade/">CryptoTrade.com</a></div>
	</footer></section>

	<!-- Swiper JS Logic -->
	<script>
	    const progressCircle = document.querySelector(".autoplay-progress svg"); const progressContent = document.querySelector(".autoplay-progress span");
	    const swiper = new Swiper(".mySwiper", { navigation: { nextEl: ".swiper-button-next", prevEl: ".swiper-button-prev" }, autoplay: { delay: 10000, disableOnInteraction: false }, pagination: { el: ".swiper-pagination", clickable: true }, on: { autoplayTimeLeft(s, time, progress) { if(progressCircle){ progressCircle.style.setProperty("--progress", 1 - progress); } if(progressContent){ progressContent.textContent = `${Math.ceil(time / 1000)}s`; } } } });
	</script>
</body>
</html>