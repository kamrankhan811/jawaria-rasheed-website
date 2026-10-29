<?php
/**
 * Website FAQ chatbot (no API key, no external service).
 * Answers from the clinic settings and links to the site's own pages. Never gives medical advice.
 *
 * @package jawaria-clinic
 */

defined( 'ABSPATH' ) || exit;

/**
 * Permalink of a page by slug (also looks under /services/).
 *
 * @param string $slug Page slug.
 * @return string
 */
function jr_bot_url( $slug ) {
	$p = get_page_by_path( $slug );
	if ( ! $p ) {
		$p = get_page_by_path( 'services/' . $slug );
	}
	return $p ? get_permalink( $p ) : home_url( '/' . $slug . '/' );
}

add_action(
	'wp_footer',
	function () {
		if ( is_admin() || '1' !== jr_opt( 'chatbot' ) ) {
			return;
		}
		$slugs = array( 'book-appointment', 'contact', 'services', 'faqs', 'about', 'andrology-iui-lab', 'iui-treatment', 'infertility-evaluation', 'ovulation-induction', 'pcos', 'pregnancy-care', 'menstrual-problems', 'fibroids', 'endometriosis', 'menopause', 'cervical-screening', 'family-planning', 'gynecology-consultation', 'videos', 'blog' );
		$u     = array();
		foreach ( $slugs as $s ) {
			$u[ $s ] = jr_bot_url( $s );
		}
		$cfg = array(
			'doctor'  => jr_opt( 'doctor_name' ),
			'title'   => jr_opt( 'title' ),
			'years'   => jr_opt( 'experience_years' ),
			'quals'   => preg_split( '/\r?\n/', trim( jr_opt( 'qualifications' ) ) ),
			'address' => implode( ' ; ', array_values( array_filter( array_map( fn( $c ) => $c['city'] ? $c['city'] . ': ' . $c['address'] : $c['address'], jr_clinics() ) ) ) ),
			'city'    => jr_opt( 'city' ),
			'map'     => jr_opt( 'map_link' ),
			'hours'   => implode( ' ; ', jr_hours_lines() ),
			'wa'      => jr_whatsapp_url(),
			'phone'   => jr_opt( 'whatsapp' ),
			'email'   => jr_opt( 'email' ),
			'areas'   => array_values( array_filter( array_map( 'trim', preg_split( '/\r?\n/', jr_opt( 'areas' ) ) ) ) ),
			'video'   => jr_opt( 'video_consult_url' ),
			'u'       => $u,
		);
		?>
<div id="jr-bot" class="jr-bot" hidden>
	<div class="jr-bot__head"><div><strong><?php echo esc_html( jr_opt( 'doctor_name' ) ); ?></strong><span>Clinic assistant · general information only</span></div><button type="button" class="jr-bot__x" aria-label="Close chat">&times;</button></div>
	<div class="jr-bot__log" role="log" aria-live="polite"></div>
	<div class="jr-bot__chips"></div>
	<form class="jr-bot__form" autocomplete="off"><input type="text" maxlength="200" placeholder="Type your question… / Sawal likhein…" aria-label="Your question"><button type="submit" aria-label="Send">&#10148;</button></form>
</div>
<button type="button" id="jr-bot-open" class="jr-bot-open" aria-label="Chat with the clinic assistant" aria-expanded="false"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 4h16a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H9l-5 4v-4H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2z"/></svg><span>Ask us</span></button>
<style>
.jr-bot-open{position:fixed;right:18px;bottom:92px;z-index:999;display:flex;align-items:center;gap:8px;background:#0f5e63;color:#fff;border:0;border-radius:30px;padding:0 18px 0 14px;height:48px;font:600 15px/1 inherit;font-family:inherit;cursor:pointer;box-shadow:0 8px 24px rgba(0,0,0,.22)}
.jr-bot-open svg{width:22px;height:22px;fill:#fff}
.jr-bot-open:hover{background:#0a4245}
.jr-bot{position:fixed;right:18px;bottom:92px;z-index:1000;width:min(370px,calc(100vw - 24px));height:min(560px,calc(100vh - 120px));display:flex;flex-direction:column;background:#fff;border-radius:16px;box-shadow:0 16px 48px rgba(0,0,0,.28);overflow:hidden;font-family:inherit;font-size:15px;line-height:1.45;color:#1f2d2e}
.jr-bot[hidden]{display:none}
.jr-bot__head{background:#0f5e63;color:#fff;padding:12px 14px;display:flex;justify-content:space-between;align-items:center}
.jr-bot__head strong{display:block;font-size:16px}
.jr-bot__head span{font-size:12.5px;opacity:.9}
.jr-bot__x{background:none;border:0;color:#fff;font-size:28px;line-height:1;cursor:pointer;padding:0 4px}
.jr-bot__log{flex:1;overflow-y:auto;padding:12px;background:#eef6f6;display:flex;flex-direction:column;gap:8px}
.jr-m{max-width:88%;padding:9px 12px;border-radius:14px;word-wrap:break-word;white-space:pre-line}
.jr-m--b{background:#fff;border:1px solid #d9e6e6;align-self:flex-start;border-bottom-left-radius:4px}
.jr-m--u{background:#0f5e63;color:#fff;align-self:flex-end;border-bottom-right-radius:4px}
.jr-m a{color:#0f5e63;font-weight:600;text-decoration:underline}
.jr-m--u a{color:#fff}
.jr-m .jr-ml{display:block;margin-top:6px}
.jr-bot__chips{display:flex;flex-wrap:wrap;gap:6px;padding:8px 10px;border-top:1px solid #d9e6e6;background:#fff;max-height:92px;overflow-y:auto}
.jr-bot__chips button{background:#f6efe4;color:#86642c;border:1px solid #e3d3b6;border-radius:16px;padding:5px 11px;font:600 13px/1.2 inherit;font-family:inherit;cursor:pointer}
.jr-bot__chips button:hover{background:#ecdcc0}
.jr-bot__form{display:flex;border-top:1px solid #d9e6e6;background:#fff}
.jr-bot__form input{flex:1;border:0;padding:12px;font:inherit;outline:0;min-width:0}
.jr-bot__form button{background:#0f5e63;color:#fff;border:0;width:52px;font-size:18px;cursor:pointer}
@media (max-width:480px){.jr-bot{right:8px;bottom:8px;width:calc(100vw - 16px);height:calc(100vh - 16px)}.jr-bot-open span{display:none}.jr-bot-open{padding:0 13px}}
</style>
<script>
(function(){
var C=<?php echo wp_json_encode( $cfg ); ?>;
var $=function(s,r){return (r||document).querySelector(s)};
var box=$('#jr-bot'),log=$('.jr-bot__log',box),chips=$('.jr-bot__chips',box),form=$('.jr-bot__form',box),inp=$('input',form),openBtn=$('#jr-bot-open');
var U=C.u,doc=C.doctor;
var L=function(a,b){return [a,b]};
/* Each intent: k = keywords (English + Roman Urdu), en/ur = reply text, l = links [label,url] */
var I={
greet:{k:['hi','hello','hey','salam','assalam','aoa','slam'],en:'Assalam o Alaikum! I am the clinic assistant for '+doc+'. I can share timings, location, services and how to book. How can I help?',ur:'Assalam o Alaikum! Main '+doc+' ke clinic ka assistant hoon. Timing, location, services aur appointment ke baare mein poochh sakte hain.',l:[]},
hours:{k:['time','timing','timings','hours','open','close','schedule','kab','waqt','wakt','ٹائم','kitne baje','baje','available','days'],en:'Clinic hours — '+C.hours+'.',ur:'Clinic ka waqt — '+C.hours+'.',l:[L('Book an appointment',U['book-appointment'])]},
book:{k:['appointment','book','booking','apointment','appoinment','slot','visit','milna','mulaqat','time lena'],en:'You can request an appointment on the booking page or on WhatsApp. The clinic will confirm your time; the date you pick is a request, not a guaranteed slot.',ur:'Appointment ke liye booking page ya WhatsApp use karein. Clinic aap ko time confirm karega; aap ki chuni hui date sirf request hoti hai.',l:[L('Book an appointment',U['book-appointment']),L('WhatsApp the clinic',C.wa)]},
fees:{k:['fee','fees','charges','charge','cost','price','rate','rates','paisay','paise','kharcha','kharch','fees kitni'],en:'Fees depend on the visit or procedure, so I cannot quote a number here. Please ask the clinic on WhatsApp or by phone.',ur:'Fees visit ya procedure par depend karti hai, is liye main yahan rate nahi bata sakta. Barah-e-karam clinic se WhatsApp ya phone par pooch lein.',l:[L('WhatsApp the clinic',C.wa)]},
loc:{k:['where','address','location','kahan','kidhar','map','direction','directions','pata','clinic kahan','route'],en:'Clinic addresses — '+C.address+'.',ur:'Clinic ka pata — '+C.address+'.',l:[L('Open in Google Maps',C.map),L('Contact page',U.contact)]},
contact:{k:['phone','number','call','contact','whatsapp','email','mail','raabta','rabta','nambar','num'],en:'WhatsApp / phone: '+C.phone+'. Email: '+C.email+'.',ur:'WhatsApp / phone: '+C.phone+'. Email: '+C.email+'.',l:[L('WhatsApp the clinic',C.wa),L('Contact page',U.contact)]},
doctor:{k:['doctor','dr','who','qualification','qualifications','degree','experience','fcps','jawaria','kon','kaun'],en:doc+' is a '+C.title+' with '+C.years+'+ years of experience. '+C.quals.join('; ')+'.',ur:doc+' '+C.years+'+ saal ke tajurbe wali '+C.title+' hain. '+C.quals.join('; ')+'.',l:[L('About the doctor',U.about)]},
services:{k:['service','services','treatment','treatments','kya kya','what do you','facilities'],en:'Services include gynecology consultation, pregnancy care, infertility evaluation, IUI, ovulation induction, PCOS, period problems, fibroids, endometriosis, menopause care, Pap smear and family planning.',ur:'Services: gynecology consultation, pregnancy care, infertility evaluation, IUI, ovulation induction, PCOS, period ke masail, fibroids, endometriosis, menopause, Pap smear aur family planning.',l:[L('All services',U.services),L('FAQs',U.faqs)]},
iui:{k:['iui','insemination','intrauterine','test tube','ivf','icsi'],en:'IUI (intrauterine insemination) is a fertility treatment. Whether it suits you needs a consultation and tests. Read the IUI page, then book a visit.',ur:'IUI (intrauterine insemination) ek fertility treatment hai. Aap ke liye munasib hai ya nahi, ye consultation aur tests ke baad pata chalta hai. IUI ka page parhein, phir appointment lein.',l:[L('IUI treatment',U['iui-treatment']),L('Book an appointment',U['book-appointment'])]},
infert:{k:['infertility','fertility','conceive','conception','pregnant nahi','bachay','bacha','baby','aulad','try','trying','not getting pregnant'],en:'Fertility concerns are assessed step by step: history, tests for both partners, then a plan. Please book a consultation.',ur:'Fertility ke masail ka jaiza qadam ba qadam hota hai: history, dono partners ke tests, phir plan. Barah-e-karam consultation book karein.',l:[L('Infertility evaluation',U['infertility-evaluation']),L('Book an appointment',U['book-appointment'])]},
lab:{k:['lab','semen','sperm','andrology','husband test','male','shohar'],en:'The clinic has an on-site andrology and IUI lab, including semen analysis.',ur:'Clinic mein andrology aur IUI lab maujood hai, jis mein semen analysis bhi hoti hai.',l:[L('Andrology & IUI lab',U['andrology-iui-lab'])]},
ovul:{k:['ovulation','follicle','follicular','monitoring'],en:'Ovulation induction and follicle monitoring are offered. Details are on the service page.',ur:'Ovulation induction aur follicle monitoring ki service hai. Tafseel service page par hai.',l:[L('Ovulation induction',U['ovulation-induction'])]},
pcos:{k:['pcos','pcod','polycystic'],en:'PCOS care is offered. Symptoms and plans differ for each woman, so please book a consultation.',ur:'PCOS ka ilaj / care hota hai. Har khatoon ki alamaat aur plan mukhtalif hote hain, is liye consultation book karein.',l:[L('PCOS care',U.pcos)]},
preg:{k:['pregnancy','pregnant','antenatal','delivery','expecting','hamal','hamla','hamil','anc'],en:'Pregnancy and antenatal care is offered. Please book a visit for your check-up.',ur:'Pregnancy aur antenatal care ki service hai. Check-up ke liye appointment lein.',l:[L('Pregnancy care',U['pregnancy-care'])]},
period:{k:['period','periods','menstrual','menses','irregular','haiz','mahwari','mahwari','bleeding between'],en:'Period problems (irregular, painful or heavy periods) are assessed in the clinic. Please book a visit.',ur:'Period ke masail (be-qaida, dard wale ya zyada periods) clinic mein dekhe jate hain. Appointment lein.',l:[L('Period problems',U['menstrual-problems'])]},
fibroid:{k:['fibroid','fibroids','gaanth','ganth','rasoli'],en:'Uterine fibroids are evaluated and managed in the clinic. Please book a visit.',ur:'Uterine fibroids ka jaiza aur ilaj clinic mein hota hai. Appointment lein.',l:[L('Fibroids',U.fibroids)]},
endo:{k:['endometriosis','pelvic pain','pelvic'],en:'Endometriosis and pelvic pain are assessed in the clinic. Please book a visit.',ur:'Endometriosis aur pelvic pain ka jaiza clinic mein hota hai. Appointment lein.',l:[L('Endometriosis',U.endometriosis)]},
meno:{k:['menopause','hot flush','hot flashes'],en:'Menopause care is offered. Please book a visit.',ur:'Menopause care ki service hai. Appointment lein.',l:[L('Menopause care',U.menopause)]},
pap:{k:['pap','smear','cervical','screening'],en:'Pap smear and cervical screening are available.',ur:'Pap smear aur cervical screening available hai.',l:[L('Cervical screening',U['cervical-screening'])]},
fp:{k:['family planning','contraception','contraceptive','birth control','iud','pills'],en:'Family planning and contraception counselling is offered. Please book a visit to choose what suits you.',ur:'Family planning aur contraception ki counselling hoti hai. Apne liye behtareen tareeqa chunne ke liye appointment lein.',l:[L('Family planning',U['family-planning'])]},
areas:{k:['multan','rahim yar khan','ryk','bahawalnagar','lodhran','vehari','ahmedpur','hasilpur','khanpur','liaquatpur','other city','dusre shehar','door'],en:'Patients visit from '+C.areas.join(', ')+'. The clinic is in '+C.city+'.',ur:'Mareez '+C.areas.join(', ')+' se aate hain. Clinic '+C.city+' mein hai.',l:[L('Location',C.map)]},
online:{k:['online','video','virtual','video call','remote','zoom'],en:C.video?'Video consultation is available. Use the link below.':'Please ask the clinic on WhatsApp whether an online consultation is available.',ur:C.video?'Video consultation available hai. Neeche link use karein.':'Online consultation ke liye clinic se WhatsApp par pooch lein.',l:C.video?[L('Video consultation',C.video)]:[L('WhatsApp the clinic',C.wa)]},
videos:{k:['videos','youtube','watch'],en:'Educational videos are on the Videos page.',ur:'Maloomati videos Videos page par hain.',l:[L('Videos',U.videos),L('Blog',U.blog)]},
thanks:{k:['thanks','thank','shukria','shukriya','jazak','jazakallah'],en:'You are welcome. Wishing you good health!',ur:'Khush raho. Allah aap ko sehat de!',l:[]}
};
var EMERG=['emergency','urgent','heavy bleeding','severe pain','faint','unconscious','miscarriage','fits','seizure','cant breathe','breathless','hospital','khoon','behosh','shadeed dard','zyada khoon'];
var URDU=['kab','kahan','kya','kitni','kitna','hai','hain','mein','main','aap','ap','karna','karni','chahiye','kaise','kese','nahi','shukria','paisay','waqt','mujhe','mera','meri','hamara','ilaj','dard','khoon','bachay','kidhar','lena','lein'];
function norm(s){return ' '+s.toLowerCase().replace(/[^a-z0-9؀-ۿ ]+/g,' ').replace(/\s+/g,' ').trim()+' '}
function has(t,k){return t.indexOf(' '+k+' ')>-1}
function isUrdu(t){var n=0;URDU.forEach(function(w){if(has(t,w))n++});return n>=1&&n>=1}
function add(cls,txt,links){var d=document.createElement('div');d.className='jr-m '+cls;d.appendChild(document.createTextNode(txt));(links||[]).forEach(function(x){if(!x[1])return;var a=document.createElement('a');a.href=x[1];a.className='jr-ml';a.textContent=x[0];if(/^https?:/.test(x[1])&&x[1].indexOf(location.host)<0){a.target='_blank';a.rel='noopener'}d.appendChild(a)});log.appendChild(d);log.scrollTop=log.scrollHeight}
function reply(q){
 var t=norm(q),ur=isUrdu(t);
 for(var i=0;i<EMERG.length;i++){if(has(t,EMERG[i])||t.indexOf(EMERG[i])>-1&&EMERG[i].indexOf(' ')>-1){add('jr-m--b',ur?'Agar yeh emergency hai (shadeed dard, zyada khoon, behoshi), foran qareebi hospital ke emergency ward jayen. Yeh chat emergency ke liye nahi hai.':'If this is an emergency (severe pain, heavy bleeding, fainting), go to the nearest hospital emergency department now. This chat is not for emergencies.',[L(ur?'Clinic ko call / WhatsApp':'Call / WhatsApp the clinic',C.wa)]);return}}
 var best=null,bs=0;
 Object.keys(I).forEach(function(k){var s=0;I[k].k.forEach(function(w){if(w.indexOf(' ')>-1?t.indexOf(' '+w+' ')>-1||t.indexOf(w)>-1:has(t,w))s+=w.length>3?2:1});if(s>bs){bs=s;best=k}});
 if(!best){add('jr-m--b',ur?'Mujhe is sawal ka jawab nahi mila. Neeche se koi option chunein, ya clinic se WhatsApp par baat karein. Main medical advice nahi de sakta.':'I could not find an answer to that. Pick an option below or message the clinic on WhatsApp. I cannot give medical advice.',[L(ur?'WhatsApp par baat karein':'WhatsApp the clinic',C.wa),L('FAQs',U.faqs)]);return}
 var r=I[best];add('jr-m--b',ur?r.ur:r.en,r.l);
}
function ask(q){q=q.trim();if(!q)return;add('jr-m--u',q);setTimeout(function(){reply(q)},250)}
[['Timings','What are the clinic timings?'],['Book appointment','How do I book an appointment?'],['Location','Where is the clinic?'],['Services','What services do you offer?'],['IUI','Tell me about IUI'],['Fees','What are the fees?'],['Roman Urdu: Timing?','Clinic ka waqt kya hai?']].forEach(function(c){var b=document.createElement('button');b.type='button';b.textContent=c[0];b.onclick=function(){ask(c[1])};chips.appendChild(b)});
form.addEventListener('submit',function(e){e.preventDefault();var v=inp.value;inp.value='';ask(v)});
var first=true;
function toggle(show){box.hidden=!show;openBtn.hidden=show;openBtn.setAttribute('aria-expanded',show);if(show){if(first){first=false;add('jr-m--b',I.greet.en+'\n\nMain Roman Urdu mein bhi jawab de sakta hoon.',[]);add('jr-m--b','This assistant gives general clinic information only, not medical advice. For emergencies go to the nearest hospital.',[])}inp.focus()}}
openBtn.onclick=function(){toggle(true)};
$('.jr-bot__x',box).onclick=function(){toggle(false)};
document.addEventListener('keydown',function(e){if(e.key==='Escape'&&!box.hidden)toggle(false)});
})();
</script>
		<?php
	},
	50
);
