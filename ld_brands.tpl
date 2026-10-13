<div class="ld-brands{if $ld_brands_scroll_active} ld-brands--scroll{/if}{if $ld_brands_scroll_active && $ld_brands_loop} ld-brands--loop{/if}">
    <div class="container">
        <div class="ld-brands__viewport">
            <div class="ld-brands__track">
                <div class="ld-brands__row">
                    {foreach from=$ld_brands item=brand}
                    <a href="{$brand.link}" class="ld-brands__item" title="{$brand.name|escape:'html':'UTF-8'}">
                        <img src="{$brand.image}" alt="{$brand.name|escape:'html':'UTF-8'}" loading="lazy">
                    </a>
                    {/foreach}
                </div>
                {if $ld_brands_scroll_active && $ld_brands_loop}
                <div class="ld-brands__row ld-brands__row--dup" aria-hidden="true">
                    {foreach from=$ld_brands item=brand}
                    <a href="{$brand.link}" class="ld-brands__item" tabindex="-1">
                        <img src="{$brand.image}" alt="" loading="lazy">
                    </a>
                    {/foreach}
                </div>
                {/if}
            </div>
        </div>
    </div>
</div>
