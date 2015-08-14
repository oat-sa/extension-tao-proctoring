<ul class="entry-point-box plain">
    {{#each entries}}
    <li>
        <a class="block entry-point" href="{{url}}">
            <h3>{{label}}</h3>
            {{#if content}}<div class="clearfix">{{content}}</div>{{/if}}
            <div class="clearfix">
                <span class="text-link" href="{{url}}"><span class="icon-play"></span>{{text}}</span>
            </div>
        </a>
    </li>
    {{/each}}
</ul>
