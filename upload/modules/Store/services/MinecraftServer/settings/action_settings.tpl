<form action="" method="post">
    <div class="form-group">
        <div class="row">
            <div class="col-md-6">
                <label for="inputTrigger">Trigger On</label>
                <select name="trigger" class="form-control" id="inputTrigger">
                    <option value="1" {if $TRIGGER_VALUE == 1} selected{/if}>Purchase</option>
                    <option value="2" {if $TRIGGER_VALUE == 2} selected{/if}>Refund</option>
                    <option value="3" {if $TRIGGER_VALUE == 3} selected{/if}>Changeback</option>
                    <option value="4" {if $TRIGGER_VALUE == 4} selected{/if}>Renewal</option>
                    <option value="5" {if $TRIGGER_VALUE == 5} selected{/if}>Expire</option>
                </select>
            </div>
            <div class="col-md-6">
                <label for="inputRequirePlayer">Require the player to be online</label>
                <select name="requirePlayer" class="form-control" id="inputRequirePlayer">
                    <option value="1" {if $REQUIRE_PLAYER_VALUE == 1} selected{/if}>Yes</option>
                    <option value="0" {if $REQUIRE_PLAYER_VALUE == 0} selected{/if}>No</option>
                </select>
            </div>
        </div>
    </div>
    <div class="form-group">
        <label for="inputConnections">{$SERVICE_CONNECTIONS}</label>
        <select name="connections[]" id="inputConnections" size="3" class="form-control" multiple style="overflow:auto;" required>
            {foreach from=$CONNECTIONS_LIST item=connection}
                <option value="{$connection.id}"{if $connection.selected} selected{/if}>{$connection.name}</option>
            {/foreach}
        </select>
    </div>
    <div class="form-group">
        <label for="InputCommand">Command (Without /)</label><a class="float-right btn btn-primary btn-sm" href="" data-toggle="modal" data-target="#placeholders">{$VIEW_PLACEHOLDERS}</a></br>
        <input type="text" name="command" class="form-control" id="InputCommand" value="{$COMMAND_VALUE}" placeholder="{literal}say Thanks {username} for purchasing {productName}{/literal}">
    </div>
    <div class="form-group custom-control custom-switch">
        <input id="inputEachQuantity" name="each_quantity" type="checkbox" class="custom-control-input"{if $EACH_QUANTITY_VALUE eq 1} checked{/if} />
        <label class="custom-control-label" for="inputEachQuantity">Run action for each quantity</label> <span
                class="badge badge-info"><i class="fas fa-question-circle"
                                            data-container="body" data-toggle="popover"
                                            data-placement="top" title="Info"
                                            data-content="Run the action for every quantity the user purchased if enabled, Otherwise the action will only be run once (Note: It will still run for each service connection)"></i></span>
    </div>
    {if $ACTION_TYPE != 'product'}
    <div class="form-group custom-control custom-switch">
        <input id="inputEachProduct" name="each_product" type="checkbox" class="custom-control-input"{if $EACH_PRODUCT_VALUE eq 1} checked{/if} />
        <label class="custom-control-label" for="inputEachProduct">Run action for each product</label> <span
                class="badge badge-info"><i class="fas fa-question-circle"
                                            data-container="body" data-toggle="popover"
                                            data-placement="top" title="Info"
                                            data-content="Run action for every product the user purchased if enabled, Otherwise the action will only be run once (Warning the product placeholders wont work)"></i></span>
    </div>
    {/if}
    <div class="form-group">
        <input type="hidden" name="token" value="{$TOKEN}">
        <input type="submit" class="btn btn-primary" value="{$SUBMIT}">
    </div>
</form>