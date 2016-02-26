<div class="diagnostics-main-area">

    <h1>{{title}}</h1>

    <div class="intro">
        {{#if header}}<p>{{header}}</p>{{/if}}
        {{#if info}}<p>{{info}}</p>{{/if}}
        <p>
            <label for="workstation">{{__ "Workstation:"}}</label>
            <input type="text" data-control="workstation" id="workstation" name="workstation" maxlength="64" placeholder="{{__ "Workstation name"}}" />
        </p>
    </div>

    <div class="clearfix">
        <button data-action="test-launcher" class="btn-info small rgt">{{button}}</button>
    </div>

    <ul class="plain results"></ul>

    <div class="status">
        <h2></h2>
    </div>

</div>
