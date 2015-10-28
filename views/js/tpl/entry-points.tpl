<ul class="entry-point-box plain">
    {{#each entries}}
    <li class="entry">
        <a class="block entry-point" href="{{url}}">
            <h3 class="title">{{label}}</h3>
            {{#if content}}<div class="content clearfix">{{{content}}}</div>{{/if}}
            <div class="bottom clearfix">
                <span class="text-link"><span class="icon-play"></span>{{text}}</span>
            </div>
        </a>
    </li>
    {{/each}}
</ul>
