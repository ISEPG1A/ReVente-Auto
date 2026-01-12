<?php
/**
 * Page "FAQ" - Questions fréquentes ReVente-Auto
 */
$nomScript = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);
$prefixeUrl = strpos($nomScript, '/public/') !== false 
    ? substr($nomScript, 0, strpos($nomScript, '/public/')) . '/' 
    : '/';
?>

<!-- Hero Section FAQ -->
<section class="faq-hero">
    <div class="faq-hero__contenu">
        <h1 class="faq-hero__titre">Questions <span style="color: white;">Fréquentes</span></h1>
        <p class="faq-hero__description">Trouvez rapidement les réponses à toutes vos questions sur ReVente Auto</p>
    </div>
    <div class="faq-hero__shapes">
        <div class="faq-shape faq-shape--1"></div>
        <div class="faq-shape faq-shape--2"></div>
    </div>
</section>

<!-- Section FAQ -->
<section class="section faq-section">
    <div class="conteneur">
        <div class="faq-intro">
            <span class="faq-badge">Notre service</span>
            <h2 class="faq-intro__titre">Tout ce que vous devez savoir</h2>
            <p class="faq-intro__text">
                Vous trouverez ci-dessous les réponses aux questions les plus fréquemment posées. 
                Si vous ne trouvez pas ce que vous cherchez, n'hésitez pas à <a href="<?= htmlspecialchars($prefixeUrl) ?>contact" class="faq-link">nous contacter</a>.
            </p>
        </div>

        <div class="faq-container">
            <!-- FAQ Item 1 -->
            <div class="faq-item">
                <button class="faq-question" aria-expanded="false">
                    <span class="faq-question__text">Comment fonctionne notre site ReVente Auto ?</span>
                    <span class="faq-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                    </span>
                </button>
                <div class="faq-answer">
                    <div class="faq-answer__content">
                        <p><strong>1 – Estimation gratuite en ligne</strong></p>
                        <p>Notre simulateur en ligne est 100% gratuit et sans engagement pour connaître l'estimation de votre véhicule selon l'année, la marque, le modèle et le kilométrage, le carburant et la boite de vitesse. Vous recevrez votre estimation directement sur le site.</p>
                        
                        <p><strong>2 – Rendez-vous avec nos experts</strong></p>
                        <p>Si vous êtes d'accord avec le montant de l'estimation envoyé par l'IA, merci de prendre rendez-vous avec l'une de nos agences pour que nos experts puissent faire un dernier contrôle sur place.</p>
                        
                        <p><strong>3 – Règlement et paiement</strong></p>
                        <p>Le règlement vous sera proposé par chèque ou par virement bancaire en prenant en charge le changement de propriétaire.</p>
                        
                        <div class="faq-highlight">
                            <strong>À savoir lors de votre rendez-vous :</strong>
                            <p>Ne pas oublier votre carte grise et prendre les doubles de vos clés ainsi que vos factures d'entretien afin d'avoir une meilleure estimation. Récupérez vos affaires personnelles avant le rendez-vous si vous voulez être payé le jour même.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- FAQ Item 2 -->
            <div class="faq-item">
                <button class="faq-question" aria-expanded="false">
                    <span class="faq-question__text">Pourquoi devrais-je passer par votre plateforme ReVente Auto pour vendre ma voiture ?</span>
                    <span class="faq-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                    </span>
                </button>
                <div class="faq-answer">
                    <div class="faq-answer__content">
                        <p>Avec notre site, vendre votre voiture n'a jamais été aussi simple. Vous serez épargné de tous les soucis que vous pouvez rencontrer lors de la vente d'un véhicule à un particulier :</p>
                        <ul>
                            <li>Poster plusieurs annonces en ligne avec des photos peu attrayantes, donc moins vendeuses</li>
                            <li>Perdre votre temps à répondre à tous les emails et faire essayer votre voiture en dehors de votre travail</li>
                            <li>Tomber sur des personnes qui négocient sans cesse le prix</li>
                            <li>Être contacté par des personnes avec des arnaques de règlement</li>
                            <li>Chaque jour ou semaine de perdus peuvent faire encore baisser la cote de votre voiture</li>
                            <li>Perdre de l'argent à remettre votre voiture en état après le contrôle technique</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- FAQ Item 3 -->
            <div class="faq-item">
                <button class="faq-question" aria-expanded="false">
                    <span class="faq-question__text">Dois-je payer des frais supplémentaires pour la reprise de ma voiture ?</span>
                    <span class="faq-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                    </span>
                </button>
                <div class="faq-answer">
                    <div class="faq-answer__content">
                        <p>Non, il n'y aura aucun frais à payer lors de la reprise de votre voiture.</p>
                    </div>
                </div>
            </div>

            <!-- FAQ Item 4 -->
            <div class="faq-item">
                <button class="faq-question" aria-expanded="false">
                    <span class="faq-question__text">Dois-je payer des frais si je n'accepte pas l'offre de votre estimation ?</span>
                    <span class="faq-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                    </span>
                </button>
                <div class="faq-answer">
                    <div class="faq-answer__content">
                        <p>Non, cette estimation est gratuite et sans engagement. Vous êtes libre de refuser notre estimation.</p>
                    </div>
                </div>
            </div>

            <!-- FAQ Item 5 -->
            <div class="faq-item">
                <button class="faq-question" aria-expanded="false">
                    <span class="faq-question__text">Comment faire si j'ai oublié des affaires dans ma voiture lors de la vente ?</span>
                    <span class="faq-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                    </span>
                </button>
                <div class="faq-answer">
                    <div class="faq-answer__content">
                        <p>Contactez rapidement notre service commercial par téléphone ou par email afin que nous puissions vérifier rapidement.</p>
                    </div>
                </div>
            </div>

            <!-- FAQ Item 6 -->
            <div class="faq-item">
                <button class="faq-question" aria-expanded="false">
                    <span class="faq-question__text">Puis-je vendre une voiture financée par un crédit ?</span>
                    <span class="faq-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                    </span>
                </button>
                <div class="faq-answer">
                    <div class="faq-answer__content">
                        <p>Oui, nous reprenons toutes sortes de voitures même avec un financement. Merci de ramener :</p>
                        <ul>
                            <li>Une attestation de votre banque de moins de 30 jours avec vos coordonnées et celles de la banque, ainsi que le montant restant à payer</li>
                            <li>Un document avec une autorisation de revente signé par vous avec le tampon de la société</li>
                            <li>Une lettre du bon règlement de votre dernière échéance</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- FAQ Item 7 -->
            <div class="faq-item">
                <button class="faq-question" aria-expanded="false">
                    <span class="faq-question__text">Peut-on vendre une voiture de fonction professionnelle ?</span>
                    <span class="faq-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                    </span>
                </button>
                <div class="faq-answer">
                    <div class="faq-answer__content">
                        <p>Oui, vous pouvez ramener une voiture appartenant à votre société. Merci de ramener :</p>
                        <ul>
                            <li>La facture d'achat</li>
                            <li>La facture de vente avec ou sans TVA</li>
                            <li>L'extrait Kbis de moins de 3 mois</li>
                            <li>La copie de votre carte d'identité</li>
                            <li>Le RIB de votre entreprise</li>
                            <li>La carte grise barrée et tamponnée</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- FAQ Item 8 -->
            <div class="faq-item">
                <button class="faq-question" aria-expanded="false">
                    <span class="faq-question__text">Si ma voiture ne roule plus, puis-je la vendre sur ReVente Auto ?</span>
                    <span class="faq-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                    </span>
                </button>
                <div class="faq-answer">
                    <div class="faq-answer__content">
                        <p>Oui, nous rachetons votre voiture non roulante ou accidentée. Nous pouvons prendre en charge le transport selon la distance avec l'une de nos agences. Contactez votre commercial pour plus d'informations.</p>
                    </div>
                </div>
            </div>

            <!-- FAQ Item 9 -->
            <div class="faq-item">
                <button class="faq-question" aria-expanded="false">
                    <span class="faq-question__text">Est-il possible de faire l'estimation d'une voiture appartenant à un proche ou à la famille ?</span>
                    <span class="faq-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                    </span>
                </button>
                <div class="faq-answer">
                    <div class="faq-answer__content">
                        <p>Oui, vous pouvez faire une estimation en ligne gratuitement même si la voiture appartient à un ami ou membre de votre famille, mais le jour de la vente, il faudra une procuration du propriétaire, sa carte grise et la copie de sa carte identité.</p>
                    </div>
                </div>
            </div>

            <!-- FAQ Item 10 -->
            <div class="faq-item">
                <button class="faq-question" aria-expanded="false">
                    <span class="faq-question__text">Peut-on revendre une voiture dont j'ai hérité lors du décès d'un membre de ma famille ?</span>
                    <span class="faq-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                    </span>
                </button>
                <div class="faq-answer">
                    <div class="faq-answer__content">
                        <p>Oui, merci de voir avec votre notaire afin d'avoir une copie de l'acte de décès et l'acte notaire ainsi que tous les documents de votre voiture pour l'estimation. Merci de ramener l'ensemble des cartes d'identité si vous êtes plusieurs à en hériter.</p>
                    </div>
                </div>
            </div>

            <!-- FAQ Item 11 -->
            <div class="faq-item">
                <button class="faq-question" aria-expanded="false">
                    <span class="faq-question__text">Puis-je vendre ma voiture si je n'ai pas changé ma carte grise et que la voiture est au nom de l'ancien propriétaire ?</span>
                    <span class="faq-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                    </span>
                </button>
                <div class="faq-answer">
                    <div class="faq-answer__content">
                        <p>Il est obligatoire de ramener votre mandat de cession de vente à votre nom et signé par le titulaire de la voiture, ainsi que la copie de votre carte d'identité ou passeport. Sinon, venez accompagné par l'ancien propriétaire.</p>
                    </div>
                </div>
            </div>

            <!-- FAQ Item 12 -->
            <div class="faq-item">
                <button class="faq-question" aria-expanded="false">
                    <span class="faq-question__text">Puis-je revendre sur ReVente Auto ma voiture sans ma carte grise ?</span>
                    <span class="faq-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                    </span>
                </button>
                <div class="faq-answer">
                    <div class="faq-answer__content">
                        <p>Votre carte grise est obligatoire lors du rachat de votre véhicule chez un professionnel.</p>
                    </div>
                </div>
            </div>

            <!-- FAQ Item 13 -->
            <div class="faq-item">
                <button class="faq-question" aria-expanded="false">
                    <span class="faq-question__text">Puis-je vendre ma voiture si elle est légèrement accidentée mais qu'elle roule ?</span>
                    <span class="faq-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                    </span>
                </button>
                <div class="faq-answer">
                    <div class="faq-answer__content">
                        <p>Oui, nous acceptons les voitures roulantes ou non mais il est préférable de contacter notre service client par téléphone ou via notre formulaire.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section CTA -->
        <div class="faq-cta">
            <div class="faq-cta__content">
                <h3 class="faq-cta__titre">Vous ne trouvez pas votre réponse ?</h3>
                <p class="faq-cta__text">Notre équipe est là pour vous aider. Contactez-nous et nous vous répondrons dans les plus brefs délais.</p>
                <a href="<?= htmlspecialchars($prefixeUrl) ?>contact" class="btn btn--principal">
                    Nous contacter
                </a>
            </div>
        </div>
    </div>
</section>

<script type="module" src="assets/js/modules/commun/VueFaq.js?v=<?= Utilitaires::versionAsset('assets/js/modules/commun/VueFaq.js') ?>"></script>
