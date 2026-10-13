<div class="panel">
    <div class="panel-heading">
        <i class="icon-cogs"></i> Paramètres
    </div>
    <div class="panel-body">
        <form action="{$ld_current_index}&token={$ld_token}" method="post">
            <div class="form-group">
                <label>Nombre de marques affichées au maximum</label>
                <input type="number" name="LD_BRANDS_LIMIT" class="form-control" style="max-width: 150px;" min="1" value="{$ld_limit}">
                <p class="help-block">
                    Si plus de marques sont marquées "Visible" (voir le tableau ci-dessous) que cette limite,
                    une sélection aléatoire différente est faite à chaque chargement de page.
                </p>
            </div>

            <div class="checkbox">
                <label>
                    <input type="checkbox" name="LD_BRANDS_SCROLL_ACTIVE" value="1" {if $ld_scroll_active}checked{/if}>
                    Défilement horizontal actif
                </label>
            </div>

            <div class="checkbox">
                <label>
                    <input type="checkbox" name="LD_BRANDS_LOOP" value="1" {if $ld_loop}checked{/if}>
                    Boucle infinie (défilement automatique) - si décoché, simple bande défilable manuellement
                </label>
            </div>

            <button type="submit" name="submitLdBrandsSettings" class="btn btn-primary" style="margin-top: 15px;">
                <i class="icon-check"></i> Enregistrer
            </button>
        </form>
    </div>
</div>
