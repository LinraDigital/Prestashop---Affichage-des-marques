<div class="panel">
    <div class="panel-heading">
        <i class="icon-tags"></i> Marques
    </div>
    <div class="panel-body">
        <p class="help-block">
            Seules les marques actives (statut natif PrestaShop) ET marquées "Visible" ici
            peuvent apparaître sur la page d'accueil.
        </p>

        <form action="{$ld_current_index}&token={$ld_token}" method="post">
            <table class="table">
                <thead>
                    <tr>
                        <th>Marque</th>
                        <th style="width: 150px;">Active (PrestaShop)</th>
                        <th style="width: 120px;">Visible</th>
                    </tr>
                </thead>
                <tbody>
                    {if $ld_manufacturers}
                        {foreach from=$ld_manufacturers item=m}
                        <tr>
                            <td>{$m.name}</td>
                            <td>
                                {if $m.active}
                                    <span class="label label-success">Oui</span>
                                {else}
                                    <span class="label label-default">Non</span>
                                {/if}
                            </td>
                            <td>
                                <label class="checkbox" style="margin: 0;">
                                    <input type="checkbox" name="ld_brands_visible[]" value="{$m.id_manufacturer}"
                                        {if $m.visible}checked{/if}>
                                </label>
                            </td>
                        </tr>
                        {/foreach}
                    {else}
                        <tr>
                            <td colspan="3">Aucune marque trouvée.</td>
                        </tr>
                    {/if}
                </tbody>
            </table>

            <button type="submit" name="submitLdBrandsVisibility" class="btn btn-primary">
                <i class="icon-check"></i> Enregistrer la visibilité
            </button>
        </form>
    </div>
</div>
