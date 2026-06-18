    </div><!-- /content -->
</div><!-- /main -->

<script>
document.querySelectorAll('.mo').forEach(function(mo){
    mo.addEventListener('click',function(e){ if(e.target===mo) mo.classList.remove('open'); });
});
function openModal(id){ document.getElementById(id).classList.add('open'); }
function closeModal(id){ document.getElementById(id).classList.remove('open'); }
</script>
</body>
</html>
