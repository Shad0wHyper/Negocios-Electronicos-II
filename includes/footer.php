<?php
// includes/footer.php
?>
</main> <!-- Cierre de site-main -->
<footer class="xanarchy-footer-wrapper">
    <!-- MARCA DE AGUA FANTASMA -->
    <div class="footer-watermark">XANARCHY</div>

    <div class="footer-inner">
        <div class="footer-top">
            <div class="footer-brand-section">
                <a href="index.php" class="footer-logo">
                    <img src="https://firebasestorage.googleapis.com/v0/b/xanarchy-store.firebasestorage.app/o/ui-assets%2FLogo_Final.png?alt=media" alt="Xanarchy Logo" />
                    <span>XANARCHY</span>
                </a>
                <p class="footer-description">
                    Xanarchy redefine el streetwear contemporáneo fusionando diseño arquitectónico, pureza geométrica y
                    lujo minimalista.
                </p>
                <div class="footer-socials">
                    <a href="#" aria-label="Instagram">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect>
                            <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path>
                            <line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line>
                        </svg>
                    </a>
                    <a href="#" aria-label="Twitter">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path
                                d="M22 4s-.7 2.1-2 3.4c1.6 10-9.4 17.3-18 11.6 2.2.1 4.4-.6 6-2C3 15.5.5 9.6 3 5c2.2 2.6 5.6 4.1 9 4-.9-4.2 4-6.6 7-3.8 1.1 0 3-1.2 3-1.2z">
                            </path>
                        </svg>
                    </a>
                    <a href="#" aria-label="TikTok">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 12a4 4 0 1 0 4 4V4a5 5 0 0 0 5 5"></path>
                        </svg>
                    </a>
                </div>
            </div>

            <div class="footer-links-grid">
                <div class="footer-link-col">
                    <p class="footer-heading">Shop</p>
                    <ul>
                        <li><a href="shop.php?category=new">Nuevos Lanzamientos</a></li>
                        <li><a href="shop.php?category=best">Best Sellers</a></li>
                        <li><a href="shop.php?category=accessories">Accesorios</a></li>
                    </ul>
                </div>
                <div class="footer-link-col">
                    <p class="footer-heading">Support</p>
                    <ul>
                        <li><a href="faq.php">FAQ</a></li>
                        <li><a href="contact.php">Contacto</a></li>
                    </ul>
                </div>
                <div class="footer-link-col">
                    <p class="footer-heading">Company</p>
                    <ul>
                        <li><a href="about.php">Sobre Nosotros</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="footer-bottom-bar">
            <p>&copy; <?php echo date("Y"); ?> Xanarchy. Todos los derechos reservados.</p>
            <div class="footer-legal-links">
                <a href="privacy.php">Privacy Policy</a>
                <a href="terms.php">Terms of Service</a>
            </div>
        </div>
    </div>
</footer>
</body>

</html>