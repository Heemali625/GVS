(function(){
    'use strict';
    if(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

    function rafThrottle(fn){
        let busy=false; return function(){ if(busy) return; busy=true; requestAnimationFrame(()=>{ fn(); busy=false; }); };
    }

    function initReveal(){
        const els = document.querySelectorAll('[data-reveal], .js-reveal');
        if(!els.length) return;
        const io = new IntersectionObserver((entries, obs)=>{
            entries.forEach(entry=>{
                if(entry.isIntersecting){
                    const el = entry.target;
                    if(el.classList.contains('stats-grid')){
                        Array.from(el.querySelectorAll('.stat')).forEach((child,i)=>{
                            setTimeout(()=>child.classList.add('in-view'), i*120);
                        });
                    }
                    el.classList.add('in-view');
                    obs.unobserve(el);
                }
            });
        },{threshold:0.12});
        els.forEach(e=>io.observe(e));
    }

    function initCounters(){
        const counters = document.querySelectorAll('.kpi-counter');
        if(!counters.length) return;
        const io = new IntersectionObserver((entries, obs)=>{
            entries.forEach(entry=>{
                if(entry.isIntersecting){
                    const el = entry.target; const target = parseInt(el.dataset.target || el.getAttribute('data-target') || 0,10);
                    if(!el.__counting){
                        el.__counting = true;
                        let start = null; const duration = 1400; const initial = 0;
                        const step = (ts)=>{
                            if(!start) start = ts;
                            const progress = Math.min((ts-start)/duration,1);
                            const value = Math.floor(progress * (target - initial) + initial);
                            el.textContent = value;
                            if(progress<1) requestAnimationFrame(step); else el.textContent = target;
                        };
                        requestAnimationFrame(step);
                    }
                    obs.unobserve(el);
                }
            });
        },{threshold:0.4});
        counters.forEach(c=>io.observe(c));
    }

    function initNavbarBlur(){
        const header = document.querySelector('.nk-header'); if(!header) return;
        const onScroll = rafThrottle(()=>{
            if(window.scrollY>24) header.classList.add('scrolled'); else header.classList.remove('scrolled');
        });
        document.addEventListener('scroll', onScroll, {passive:true});
        onScroll();
    }

    function initParallax(){
        const els = document.querySelectorAll('.parallax-min'); if(!els.length) return;
        const onScroll = rafThrottle(()=>{
            const vh = window.innerHeight;
            els.forEach(el=>{
                const rect = el.getBoundingClientRect();
                const speed = parseFloat(el.dataset.parallaxSpeed || el.getAttribute('data-parallax-speed') || 0.04);
                const offset = (rect.top - vh/2) * speed;
                el.style.transform = `translate3d(0, ${offset}px, 0)`;
            });
        });
        document.addEventListener('scroll', onScroll, {passive:true});
        window.addEventListener('resize', onScroll);
        onScroll();
    }

    function initTimeline(){
        const timelines = document.querySelectorAll('.timeline'); if(!timelines.length) return;
        const io = new IntersectionObserver((entries, obs)=>{
            entries.forEach(entry=>{
                if(entry.isIntersecting){
                    const t = entry.target; t.classList.add('animate');
                    const nodes = t.querySelectorAll('.timeline-item');
                    nodes.forEach((node,i)=>{
                        setTimeout(()=>node.classList.add('active'), i*220);
                    });
                    obs.unobserve(t);
                }
            });
        },{threshold:0.2});
        timelines.forEach(t=>io.observe(t));
    }

    function initAll(){ initReveal(); initCounters(); initNavbarBlur(); initParallax(); initTimeline(); }
    if(document.readyState==='loading') document.addEventListener('DOMContentLoaded', initAll); else initAll();
})();
