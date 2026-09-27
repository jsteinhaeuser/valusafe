<?php
/**
 * Backend Layout - Footer
 */
?>
    </div> <!-- /.backend-container -->
    
    <!-- Footer (optional) -->
    <footer class="backend-footer">
        <div class="footer-content">
            <span>&copy; <?php echo date('Y'); ?> Wertsachen Inventar</span>
            <span class="footer-separator">•</span>
            <span>Admin Backend v2.0</span>
            <span class="footer-separator">•</span>
            <span><?php echo count(glob('../upload/*')); ?> Dateien</span>
        </div>
    </footer>
    
    <script src="../js/context_help.js"></script>
</body>
</html>
