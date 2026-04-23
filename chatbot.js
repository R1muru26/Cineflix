/**
 * CineFlix Chatbot - Food Ordering, Seat Recommendations, Order Tracking
 * Self-contained: injects its own CSS and DOM elements.
 */
(function () {
  'use strict';

  /* ── API PATHS (auto-detects subfolder e.g. /CINEFLIX2/) ──────────────── */
  var _dir        = window.location.pathname.substring(0, window.location.pathname.lastIndexOf('/') + 1);
  var API         = _dir + 'api/chatbot.php';
  var TRACK_API   = _dir + 'api/food_track.php';
  var RECEIPT_API = _dir + 'api/receipt.php';
  var LOGIN_URL   = _dir + 'login.html';

  /* ── MENU ─────────────────────────────────────────────────────────────── */
  var MENU = [
    { id: 'popcorn', name: 'Popcorn',         price: 150, emoji: '🍿' },
    { id: 'drink',   name: 'Drink',           price: 120, emoji: '🥤' },
    { id: 'nachos',  name: 'Nachos',          price: 180, emoji: '🧆' },
    { id: 'hotdog',  name: 'Hotdog Sandwich', price: 100, emoji: '🌭' }
  ];

  /* ── INJECT CSS ───────────────────────────────────────────────────────── */
  if (!document.getElementById('cfStyles')) {
    var s = document.createElement('style');
    s.id  = 'cfStyles';
    s.textContent = [
      '#cfWidget{position:fixed;bottom:var(--fs-spacing-lg,24px);right:var(--fs-spacing-lg,24px);z-index:2147483647;font-family:var(--fs-font-family,Poppins,sans-serif)}',
      '#cfToggle{width:60px;height:60px;border-radius:50%;background:linear-gradient(135deg,var(--fs-color-primary,#c79f5e),var(--fs-color-primary-dark,#d98639));border:none;color:#fff;font-size:26px;cursor:pointer;box-shadow:var(--fs-shadow-lg);transition:transform var(--fs-transition-fast, .2s)}',
      '#cfToggle:hover{transform:scale(1.09);box-shadow:var(--fs-shadow-xl)}',
      '#cfPanel{position:absolute;bottom:72px;right:0;width:390px;max-width:calc(100vw - 48px);height:540px;background:var(--fs-color-bg-dark,#1e1f27);border:1px solid var(--fs-color-border,rgba(255,255,255,.1));border-radius:var(--fs-radius-lg,16px);box-shadow:var(--fs-shadow-xl);display:none;flex-direction:column;overflow:hidden;transition:all var(--fs-transition-fast)}',
      '#cfHdr{padding:var(--fs-spacing-md,12px) var(--fs-spacing-lg,16px);background:linear-gradient(135deg,#1a1a2e,var(--fs-color-primary-dark,#d98639));color:#fff;display:flex;align-items:center;gap:10px;flex-shrink:0}',
      '#cfHdr h3{margin:0;font-size:var(--fs-font-size-base,.94rem);flex:1;font-family:var(--fs-font-family,Poppins,sans-serif)}',
      '#cfCartIcon{background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.3);color:#fff;border-radius:var(--fs-radius-full,20px);padding:5px 12px;font-size:.8rem;cursor:pointer;display:flex;align-items:center;gap:5px;font-family:var(--fs-font-family,Poppins,sans-serif);transition:background var(--fs-transition-fast)}',
      '#cfCartIcon:hover{background:rgba(255,255,255,.26)}',
      '#cfBadge{background:var(--fs-color-error,#e53935);color:#fff;border-radius:50%;min-width:18px;height:18px;font-size:.66rem;font-weight:700;padding:0 3px;display:none;align-items:center;justify-content:center}',
      '#cfQuick{display:flex;flex-wrap:wrap;gap:6px;padding:9px 12px;border-bottom:1px solid var(--fs-color-border,rgba(255,255,255,.08));background:rgba(0,0,0,.2);flex-shrink:0}',
      '.cfQBtn{padding:5px 11px;border-radius:var(--fs-radius-full,20px);border:1px solid rgba(255,255,255,.2);background:rgba(255,255,255,.06);color:#fff;font-size:.73rem;font-family:var(--fs-font-family,Poppins,sans-serif);cursor:pointer;transition:all var(--fs-transition-fast)}',
      '.cfQBtn:hover{background:var(--fs-color-primary,#c79f5e);border-color:var(--fs-color-primary,#c79f5e)}',
      '#cfMsgs{flex:1;overflow-y:auto;padding:14px;display:flex;flex-direction:column;gap:10px}',
      '#cfMsgs::-webkit-scrollbar{width:4px}',
      '#cfMsgs::-webkit-scrollbar-thumb{background:rgba(255,255,255,.2);border-radius:4px}',
      '.cfMsg{max-width:88%;padding:10px 14px;border-radius:var(--fs-radius-md,12px);font-size:.86rem;line-height:1.55;word-break:break-word;font-family:var(--fs-font-family,Poppins,sans-serif)}',
      '.cfMsg.u{align-self:flex-end;background:linear-gradient(135deg,var(--fs-color-primary,#c79f5e),var(--fs-color-primary-dark,#d98639));color:#fff}',
      '.cfMsg.b{align-self:flex-start;background:rgba(255,255,255,.08);border:1px solid var(--fs-color-border,rgba(255,255,255,.1));color:#eaeaea}',
      '.cfMsg.ok{max-width:96%;background:rgba(38,222,129,.13)!important;border:1px solid rgba(38,222,129,.4)!important}',
      '.cfChips{display:flex;flex-wrap:wrap;gap:6px;margin-top:8px}',
      '.cfChip{padding:5px 11px;border-radius:var(--fs-radius-full,20px);background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.2);color:#fff;font-size:.76rem;font-family:var(--fs-font-family,Poppins,sans-serif);cursor:pointer;transition:all var(--fs-transition-fast)}',
      '.cfChip:hover{background:rgba(199,159,94,.4);border-color:var(--fs-color-primary,#c79f5e)}',
      '.cfMenuWrap{margin-top:10px;display:flex;flex-direction:column;gap:8px}',
      '.cfMRow{display:flex;align-items:center;justify-content:space-between;background:rgba(255,255,255,.06);border-radius:var(--fs-radius-md,8px);padding:8px 10px}',
      '.cfMName{font-size:.83rem;font-weight:var(--fs-font-weight-medium,500);color:#eaeaea}',
      '.cfMPrice{color:var(--fs-color-primary,#c79f5e);font-weight:var(--fs-font-weight-semibold,600);margin-left:5px}',
      '.cfQCtrl{display:flex;align-items:center;gap:6px}',
      '.cfQB{width:26px;height:26px;border-radius:50%;border:1px solid rgba(255,255,255,.3);background:rgba(255,255,255,.1);color:#fff;font-size:.95rem;cursor:pointer;line-height:1;transition:all var(--fs-transition-fast)}',
      '.cfQB:hover{background:var(--fs-color-primary,#c79f5e);border-color:var(--fs-color-primary,#c79f5e)}',
      '.cfQV{min-width:20px;text-align:center;font-weight:var(--fs-font-weight-semibold,600);color:#fff;font-size:.9rem}',
      '.cfAddBtn{margin-top:4px;padding:9px 16px;background:linear-gradient(135deg,var(--fs-color-primary,#c79f5e),var(--fs-color-primary-dark,#d98639));border:none;border-radius:var(--fs-radius-md,8px);color:#fff;font-weight:var(--fs-font-weight-semibold,600);font-family:var(--fs-font-family,Poppins,sans-serif);font-size:.83rem;cursor:pointer;transition:all var(--fs-transition-fast)}',
      '.cfAddBtn:disabled{cursor:default;opacity:.7;transform:none}',
      '.cfAddBtn:hover:not(:disabled){transform:translateY(-2px);box-shadow:var(--fs-shadow-md)}',
      '.cfActRow{display:flex;flex-wrap:wrap;gap:8px;margin-top:10px}',
      '.cfTBtn{padding:8px 14px;background:var(--fs-color-success,#26de81);color:#fff;border-radius:var(--fs-radius-md,8px);font-size:.81rem;border:none;font-weight:var(--fs-font-weight-semibold,600);font-family:var(--fs-font-family,Poppins,sans-serif);cursor:pointer;transition:all var(--fs-transition-fast)}',
      '.cfTBtn:hover{background:#20c76f;transform:translateY(-2px)}',
      '.cfRBtn{padding:8px 14px;background:rgba(255,255,255,.14);border:1px solid rgba(255,255,255,.28);border-radius:var(--fs-radius-md,8px);color:#fff;font-size:.81rem;font-family:var(--fs-font-family,Poppins,sans-serif);cursor:pointer;font-weight:var(--fs-font-weight-semibold,600);transition:all var(--fs-transition-fast)}',
      '.cfRBtn:hover{background:rgba(255,255,255,.2);transform:translateY(-2px)}',
      '.cfLBtn{padding:8px 14px;background:var(--fs-color-error,#e53935);border:none;border-radius:var(--fs-radius-md,8px);color:#fff;font-size:.81rem;font-family:var(--fs-font-family,Poppins,sans-serif);cursor:pointer;font-weight:var(--fs-font-weight-semibold,600);transition:all var(--fs-transition-fast)}',
      '.cfLBtn:hover{background:#d32f2f;transform:translateY(-2px)}',
      '#cfInpArea{padding:10px 12px;border-top:1px solid var(--fs-color-border,rgba(255,255,255,.1));display:flex;gap:8px;flex-shrink:0}',
      '#cfInpArea input{flex:1;padding:9px 14px;border:1px solid rgba(255,255,255,.18);border-radius:var(--fs-radius-full,24px);background:rgba(255,255,255,.06);color:#fff;font-size:.86rem;font-family:var(--fs-font-family,Poppins,sans-serif);outline:none;transition:border-color var(--fs-transition-fast)}',
      '#cfInpArea input:focus{border-color:var(--fs-color-primary,#c79f5e)}',
      '#cfInpArea input::placeholder{color:rgba(255,255,255,.38)}',
      '#cfInpArea button{padding:9px 18px;background:linear-gradient(135deg,var(--fs-color-primary,#c79f5e),var(--fs-color-primary-dark,#d98639));border:none;border-radius:var(--fs-radius-full,24px);color:#fff;font-weight:var(--fs-font-weight-semibold,600);font-family:var(--fs-font-family,Poppins,sans-serif);cursor:pointer;transition:all var(--fs-transition-fast)}',
      '#cfInpArea button:hover{transform:translateY(-2px);box-shadow:var(--fs-shadow-md)}',
      '#cfOverlay{position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:2147483644;display:none;backdrop-filter:blur(4px)}',
      /* Cart panel */
      '#cfCartPanel{position:fixed;top:0;right:0;width:400px;max-width:100vw;height:100vh;background:#fff;z-index:2147483646;box-shadow:-8px 0 40px rgba(0,0,0,.35);display:none;flex-direction:column;overflow:hidden;font-family:var(--fs-font-family,Poppins,sans-serif);transition:transform var(--fs-transition-fast)}',
      '#cfCartHdr{padding:18px 20px;background:linear-gradient(135deg,var(--fs-color-primary,#c79f5e),var(--fs-color-primary-dark,#d98639));color:#fff;display:flex;align-items:center;justify-content:space-between;flex-shrink:0}',
      '#cfCartHdr h2{margin:0;font-size:1.1rem;font-family:var(--fs-font-family,Poppins,sans-serif)}',
      '.cfClose{background:rgba(255,255,255,.22);border:none;color:#fff;width:34px;height:34px;border-radius:50%;font-size:20px;cursor:pointer;line-height:1;display:flex;align-items:center;justify-content:center;transition:background var(--fs-transition-fast)}',
      '.cfClose:hover{background:rgba(255,255,255,.38)}',
      '#cfCartBody{flex:1;overflow-y:auto;padding:20px;display:flex;flex-direction:column;gap:14px;color:#333}',
      '#cfCartEmpty{display:flex;flex-direction:column;align-items:center;justify-content:center;gap:8px;color:#999;padding:48px 20px;text-align:center;flex:1}',
      '#cfCartEmpty .cfBigIcon{font-size:3.2rem}',
      '#cfCartEmpty p{font-size:1rem;font-weight:var(--fs-font-weight-semibold,600);color:#666}',
      '#cfCartItems{display:none;flex-direction:column;gap:10px}',
      '.cfCI{background:#f8f9fa;border-radius:var(--fs-radius-md,10px);padding:14px;display:flex;flex-direction:column;gap:8px;border:1px solid #efefef;transition:transform var(--fs-transition-fast)}',
      '.cfCI:hover{transform:translateX(-4px);border-color:var(--fs-color-primary)}',
      '.cfCI1{display:flex;justify-content:space-between;align-items:center}',
      '.cfCIName{font-weight:var(--fs-font-weight-semibold,600);font-size:.92rem;color:#111}',
      '.cfCITot{font-weight:var(--fs-font-weight-bold,700);color:var(--fs-color-primary,#c79f5e);font-size:.92rem}',
      '.cfCI2{display:flex;align-items:center;gap:8px}',
      '.cfCIQ{width:30px;height:30px;border-radius:50%;border:2px solid var(--fs-color-primary,#c79f5e);background:#fff;color:var(--fs-color-primary,#c79f5e);font-size:1rem;cursor:pointer;font-weight:var(--fs-font-weight-bold,700);display:flex;align-items:center;justify-content:center;transition:all var(--fs-transition-fast)}',
      '.cfCIQ:hover{background:var(--fs-color-primary,#c79f5e);color:#fff}',
      '.cfCIQty{min-width:28px;text-align:center;font-weight:var(--fs-font-weight-bold,700);font-size:.95rem;color:#111}',
      '.cfCIUnit{font-size:.79rem;color:#aaa}',
      '.cfCIRm{margin-left:auto;background:none;border:none;cursor:pointer;font-size:1.1rem;opacity:.4;transition:opacity var(--fs-transition-fast)}',
      '.cfCIRm:hover{opacity:1;color:var(--fs-color-error)}',
      '#cfCartSum{display:none;background:#f4f4f4;border-radius:var(--fs-radius-md,10px);padding:16px;flex-direction:column;gap:7px}',
      '.cfSR{display:flex;justify-content:space-between;font-size:.9rem;color:#555}',
      '.cfSR.disc span:last-child{color:var(--fs-color-success,#26de81);font-weight:var(--fs-font-weight-semibold,600)}',
      '.cfSR.tot{font-weight:var(--fs-font-weight-bold,700);font-size:1rem;color:#111;border-top:2px solid rgba(199,159,94,.35);padding-top:10px;margin-top:4px}',
      '.cfSR.tot span:last-child{color:var(--fs-color-primary,#c79f5e)}',
      '#cfChkSec{display:none;flex-direction:column;gap:10px}',
      '.cfChkLbl{font-weight:var(--fs-font-weight-semibold,600);font-size:.9rem;color:#333}',
      '#cfChkSec small{font-size:.74rem;color:#999}',
      '.cfSeatRow{display:flex;gap:8px}',
      '.cfSeatRow input{flex:1;padding:11px 14px;border:2px solid #e0e0e0;border-radius:var(--fs-radius-md,8px);font-size:.95rem;font-family:var(--fs-font-family,Poppins,sans-serif);outline:none;text-transform:uppercase;transition:border-color var(--fs-transition-fast)}',
      '.cfSeatRow input:focus{border-color:var(--fs-color-primary,#c79f5e)}',
      '.cfSeatRow input.cfshk{animation:cfshk .45s;border-color:var(--fs-color-error,#e53935)}',
      '@keyframes cfshk{0%,100%{transform:translateX(0)}25%{transform:translateX(-6px)}75%{transform:translateX(6px)}}',
      '.cfPlaceBtn{position:relative;padding:11px 18px;background:linear-gradient(135deg,var(--fs-color-primary,#c79f5e),var(--fs-color-primary-dark,#d98639));border:none;border-radius:var(--fs-radius-md,8px);color:#fff;font-weight:var(--fs-font-weight-bold,700);font-family:var(--fs-font-family,Poppins,sans-serif);font-size:.87rem;cursor:pointer;white-space:nowrap;transition:all .3s ease;overflow:hidden;min-width:140px}',
      '.cfPlaceBtn:disabled{cursor:default;opacity:.65;transform:none}',
      '.cfPlaceBtn:hover:not(:disabled){transform:translateY(-2px);box-shadow:var(--fs-shadow-lg)}',
      '.cfPlaceBtn.cf-btn-processing{background:linear-gradient(135deg,#4a9eff,#2979ff);animation:cfBtnPulse 1.5s ease-in-out infinite}',
      '@keyframes cfBtnPulse{0%,100%{box-shadow:0 0 0 0 rgba(74,158,255,.6)}50%{box-shadow:0 0 0 10px rgba(74,158,255,0)}}',
      '.cfPlaceBtn.cf-btn-processing::after{content:"";position:absolute;top:0;left:-100%;width:60%;height:100%;background:linear-gradient(90deg,transparent,rgba(255,255,255,.25),transparent);animation:cfBtnShimmer 1.4s linear infinite}',
      '@keyframes cfBtnShimmer{0%{left:-60%}100%{left:140%}}',
      /* Tracker panel */
      /* ── ORDER TRACKER PANEL — matches reference image design ── */
      '#cfTrkPanel{position:fixed;top:0;right:0;width:390px;max-width:100vw;height:100vh;background:#f2f2f2;z-index:2147483646;box-shadow:-8px 0 40px rgba(0,0,0,.25);display:none;flex-direction:column;overflow:hidden;font-family:var(--fs-font-family,Poppins,sans-serif);transition:transform var(--fs-transition-fast)}',
      /* Gold gradient header matching reference */
      '#cfTrkHdr{padding:14px 16px;background:linear-gradient(135deg,#b8892a 0%,#d4a84b 40%,#c9973c 70%,#8b6419 100%);color:#fff;display:flex;justify-content:space-between;align-items:center;flex-shrink:0;box-shadow:0 2px 12px rgba(180,130,30,.35)}',
      '#cfTrkHdr h2{margin:0;font-size:.95rem;font-weight:700;font-family:var(--fs-font-family,Poppins,sans-serif);display:flex;align-items:center;gap:8px;letter-spacing:.2px}',
      '#cfTrkHdrR{display:flex;align-items:center;gap:8px}',
      /* Receipt button — pill shape matching reference */
      '#cfRcptBtn{padding:5px 14px;background:rgba(255,255,255,.18);border:1.5px solid rgba(255,255,255,.55);border-radius:20px;color:#fff;font-size:.75rem;font-family:var(--fs-font-family,Poppins,sans-serif);cursor:pointer;font-weight:600;transition:all .2s;display:flex;align-items:center;gap:5px}',
      '#cfRcptBtn:hover{background:rgba(255,255,255,.32)}',
      /* Scrollable body — light grey background */
      '#cfTrkBody{flex:1;overflow-y:auto;padding:14px 14px 20px;display:flex;flex-direction:column;gap:12px;color:#333;background:#f2f2f2}',
      '#cfTrkBody::-webkit-scrollbar{width:3px}',
      '#cfTrkBody::-webkit-scrollbar-thumb{background:rgba(0,0,0,.15);border-radius:3px}',
      /* Banner card — dark navy with order id left, status right */
      '#cfTrkBanner{background:linear-gradient(135deg,#1a1a2e 0%,#252540 100%);border-radius:14px;padding:16px 18px;color:#fff;display:flex;justify-content:space-between;align-items:center;box-shadow:0 4px 18px rgba(0,0,0,.22)}',
      '.cfBanLbl{font-size:.6rem;color:rgba(255,255,255,.45);text-transform:uppercase;letter-spacing:1px;margin-bottom:3px}',
      '.cfBanOid{font-size:.82rem;font-weight:700;letter-spacing:.3px;color:#fff}',
      '.cfBanR{text-align:right}',
      /* "Done / timer" — large gold text matching reference */
      '.cfEtaN{font-size:2rem;font-weight:800;color:#c9a84c;line-height:1;font-family:var(--fs-font-family,Poppins,sans-serif)}',
      '.cfEtaL{font-size:.62rem;color:rgba(255,255,255,.5);margin-top:3px;text-align:right}',
      /* Progress card — pure white rounded card */
      '#cfProgCard{background:#fff;border-radius:14px;padding:18px 14px 16px;box-shadow:0 2px 10px rgba(0,0,0,.07)}',
      '#cfProgTitle{font-size:.68rem;font-weight:700;color:#999;text-align:left;margin-bottom:16px;text-transform:uppercase;letter-spacing:.9px;font-family:var(--fs-font-family,Poppins,sans-serif)}',
      '#cfProgSteps{display:flex;justify-content:space-between;align-items:flex-start;position:relative;padding:0 4px}',
      /* Grey baseline track — sits at centre of 44px dots */
      '#cfProgSteps::before{content:"";position:absolute;top:22px;left:calc(10% + 22px);right:calc(10% + 22px);height:2px;background:#e0e0e0;z-index:0}',
      /* Amber fill track */
      '#cfProgFill{position:absolute;top:22px;left:calc(10% + 22px);height:2px;background:linear-gradient(90deg,#c9a84c,#e8c97a);z-index:1;width:0;transition:width 1s cubic-bezier(.4,0,.2,1)}',
      '@keyframes cfpulse{0%,100%{box-shadow:0 0 0 0 rgba(201,168,76,.5)}50%{box-shadow:0 0 0 8px rgba(201,168,76,0)}}',
      '.cfStep{display:flex;flex-direction:column;align-items:center;gap:6px;z-index:2;flex:1}',
      /* Amber/gold dot — matches reference image circles */
      '.cfStepDot{width:44px;height:44px;border-radius:50%;display:flex;align-items:center;justify-content:center;background:#f0e6cc;border:2px solid #e8d5a3;transition:all .35s}',
      '.cfStepDot svg{width:20px;height:20px;stroke:#c9a84c;fill:none;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round;transition:stroke .35s}',
      /* Done — solid amber */
      '.cfStep.done .cfStepDot{background:#c9a84c;border-color:#c9a84c}',
      '.cfStep.done .cfStepDot svg{stroke:#fff}',
      /* Current — amber with pulse ring */
      '.cfStep.cur .cfStepDot{background:#c9a84c;border-color:#c9a84c;box-shadow:0 0 0 5px rgba(201,168,76,.22);animation:cfpulse 2s infinite}',
      '.cfStep.cur .cfStepDot svg{stroke:#fff}',
      /* Pending — muted */
      '.cfStep.pend .cfStepDot{background:#f5f5f5;border-color:#e0e0e0}',
      '.cfStep.pend .cfStepDot svg{stroke:#ccc}',
      /* Delivered last step — green */
      '.cfStep.done.cfDelivered .cfStepDot{background:#26de81;border-color:#26de81}',
      '.cfStep.cur.cfDelivered  .cfStepDot{background:#26de81;border-color:#26de81;box-shadow:0 0 0 5px rgba(38,222,129,.2)}',
      /* Labels */
      '.cfStepLbl{font-size:.6rem;font-weight:600;text-align:center;color:#bbb;line-height:1.3;max-width:52px;font-family:var(--fs-font-family,Poppins,sans-serif)}',
      '.cfStep.done .cfStepLbl{color:#c9a84c}',
      '.cfStep.cur  .cfStepLbl{color:#c9a84c;font-weight:700}',
      '.cfStep.done.cfDelivered .cfStepLbl{color:#26de81}',
      '.cfStep.cur.cfDelivered  .cfStepLbl{color:#26de81}',
      '.cfStep.pulse .cfStepDot{animation:stepPulse 2s ease-in-out infinite}',
      '.cfStep.pulse .cfStepDot svg{animation:iconPulse 2s ease-in-out infinite}',
      '@keyframes stepPulse{0%,100%{transform:scale(1);box-shadow:0 0 0 0 rgba(201,168,76,.6)}50%{transform:scale(1.08);box-shadow:0 0 16px rgba(201,168,76,.35)}}',
      '@keyframes iconPulse{0%,100%{transform:scale(1)}50%{transform:scale(1.15)}}',
      '#cfProgCard.floating{animation:cardFloat 3s ease-in-out infinite}',
      '@keyframes cardFloat{0%,100%{transform:translateY(0)}50%{transform:translateY(-4px)}}',
      '#cfTrkPanel.delivered{animation:panelCelebrate 0.6s ease-out}',
      '@keyframes panelCelebrate{0%{transform:scale(1)}50%{transform:scale(1.04)}100%{transform:scale(1)}}',
      /* Status card — white card with amber icon circle */
      '#cfStCard{background:#fff;border-radius:14px;padding:12px 16px;display:flex;align-items:center;gap:14px;box-shadow:0 2px 10px rgba(0,0,0,.07)}',
      '#cfStIcon{width:42px;height:42px;border-radius:50%;background:#f0e6cc;display:flex;align-items:center;justify-content:center;flex-shrink:0;border:2px solid #e8d5a3}',
      '#cfStIcon svg{width:20px;height:20px;stroke:#c9a84c;fill:none;stroke-width:2;stroke-linecap:round;stroke-linejoin:round}',
      '#cfStTitle{font-weight:700;font-size:.9rem;color:#222}',
      '#cfStSub{font-size:.74rem;color:#999;margin-top:1px}',
      /* Items ordered card — white card matching reference */
      '#cfTrkItemsCard{background:#fff;border-radius:14px;padding:14px 16px;display:none;box-shadow:0 2px 10px rgba(0,0,0,.07)}',
      '.cfTITitle{font-size:.62rem;font-weight:700;text-transform:uppercase;letter-spacing:.9px;color:#aaa;margin-bottom:10px}',
      '.cfTIRow{display:flex;justify-content:space-between;font-size:.84rem;padding:5px 0;border-bottom:1px solid #f4f4f4;color:#444}',
      '.cfTIRow:last-child{border-bottom:none}',
      '.cfTITot{font-weight:700;color:#111;border-top:2px solid #f0e6cc!important;padding-top:8px;margin-top:4px}',
      /* Seat footer strip — warm amber tinted */
      '#cfTrkSeat{background:#fdf6e8;border:1px solid rgba(201,168,76,.3);border-radius:12px;padding:10px 15px;font-size:.83rem;color:#666;display:none;align-items:center;gap:8px}',
      '#cfTrkSeat strong{color:#b8892a;font-weight:700}',
      '@media(max-width:480px){#cfWidget{bottom:var(--fs-spacing-sm,16px);right:var(--fs-spacing-sm,16px)}#cfPanel{width:calc(100vw - 32px);height:88vh;right:-8px}#cfToggle{width:52px;height:52px;font-size:20px}#cfTrkPanel,#cfCartPanel{width:100%;max-width:100%}}'
    ].join('');
    document.head.appendChild(s);
  }

  /* ── BUILD DOM ────────────────────────────────────────────────────────── */
  function buildDOM() {
    /* Widget */
    var w = document.createElement('div');
    w.id = 'cfWidget';
    w.innerHTML =
      '<button id="cfToggle">💬</button>' +
      '<div id="cfPanel">' +
        '<div id="cfHdr"><span>🎬</span><h3>CineFlix Assistant</h3>' +
          '<button id="cfCartIcon">🛒 <span id="cfBadge">0</span></button>' +
        '</div>' +
        '<div id="cfQuick">' +
          '<button class="cfQBtn" data-a="mood">🎭 Mood</button>' +
          '<button class="cfQBtn" data-a="seats">🪑 Seats</button>' +
          '<button class="cfQBtn" data-a="worth">⭐ Worth It?</button>' +
          '<button class="cfQBtn" data-a="timing">⏰ Timing</button>' +
          '<button class="cfQBtn" data-a="group">👥 Group</button>' +
          '<button class="cfQBtn" data-a="food">🍿 Food</button>' +
          '<button class="cfQBtn" data-a="cart">🛒 Cart</button>' +
          '<button class="cfQBtn" data-a="track">📦 Track</button>' +
        '</div>' +
        '<div id="cfMsgs"></div>' +
        '<div id="cfInpArea">' +
          '<input type="text" id="cfInput" placeholder="Ask about seats, food, or orders...">' +
          '<button id="cfSendBtn">Send</button>' +
        '</div>' +
      '</div>';
    document.body.appendChild(w);

    /* Overlay */
    var ov = document.createElement('div');
    ov.id = 'cfOverlay';
    document.body.appendChild(ov);

    /* Cart panel */
    var cp = document.createElement('div');
    cp.id = 'cfCartPanel';
    cp.innerHTML =
      '<div id="cfCartHdr"><h2>🛒 My Cart</h2>' +
        '<button class="cfClose" id="cfCartClose">x</button>' +
      '</div>' +
      '<div id="cfCartBody">' +
        '<div id="cfCartEmpty">' +
          '<span class="cfBigIcon">🍿</span>' +
          '<p>Your cart is empty</p>' +
          '<small>Browse the menu and add items</small>' +
        '</div>' +
        '<div id="cfCartItems"></div>' +
        '<div id="cfCartSum">' +
          '<div class="cfSR"><span>Subtotal</span><span id="cfSub">&#8369;0.00</span></div>' +
          '<div id="cfDiscRow" class="cfSR disc" style="display:none"><span>Discount (20% PWD/Senior)</span><span id="cfDisc" style="color:#26de81">-&#8369;0.00</span></div>' +
          '<div class="cfSR tot"><span>Total</span><span id="cfTot">&#8369;0.00</span></div>' +
        '</div>' +
        /* PWD/Senior discount box — always visible when cart has items */
        '<div id="cfDiscSec" style="display:none;flex-direction:column;gap:8px;background:#fffbf4;border:1.5px solid #e8d5a3;border-radius:10px;padding:12px 14px;margin-top:4px">' +
          '<div style="display:flex;align-items:center;gap:7px">' +
            '<span style="font-size:1.1rem">🪪</span>' +
            '<span style="font-weight:700;font-size:.86rem;color:#333">PWD / Senior Citizen Discount</span>' +
          '</div>' +
          '<p style="font-size:.78rem;color:#777;margin:0">Have a PWD or Senior Citizen ID? Enter the ID number below to get <strong style=\"color:#c79f5e\">20% off</strong> your order.</p>' +
          '<div style="display:flex;gap:7px;align-items:stretch">' +
            '<input type="text" id="cfDiscInp" placeholder="e.g. PWD-2024-001234" maxlength="30"' +
              ' style="flex:1;padding:10px 12px;border:2px solid #ddd;border-radius:8px;font-size:.84rem;font-family:Poppins,sans-serif;outline:none;color:#333;background:#fff">' +
            '<button id="cfDiscApplyBtn"' +
              ' style="padding:10px 14px;background:linear-gradient(135deg,#c79f5e,#d98639);border:none;border-radius:8px;color:#fff;font-weight:700;font-family:Poppins,sans-serif;font-size:.82rem;cursor:pointer;white-space:nowrap;flex-shrink:0">Apply</button>' +
          '</div>' +
          '<div id="cfDiscMsg" style="font-size:.76rem;color:#999;min-height:16px"></div>' +
        '</div>' +
        '<div id="cfChkSec" style="display:none;flex-direction:column;gap:10px">' +
          '<div class="cfChkLbl">Deliver to seat:</div>' +
          '<input type="text" id="cfSeatInp" placeholder="e.g. A5, B12" maxlength="10"' +
            ' style="padding:10px 12px;border:2px solid #ddd;border-radius:8px;font-size:.84rem;font-family:Poppins,sans-serif;outline:none;color:#333;background:#fff;text-transform:uppercase">' +
          '<small style="font-size:.74rem;color:#999">Row A-H + seat 1-10, e.g. D5</small>' +
          '<button id="cfPlaceBtn" class="cfPlaceBtn" style="width:100%;margin-top:8px">🛒 Place Order</button>' +
          '<button id="cfClearBtn" style="padding:8px 14px;background:rgba(244,67,54,0.9);border:none;border-radius:8px;color:#fff;font-weight:600;font-family:Poppins,sans-serif;font-size:.81rem;cursor:pointer;width:100%">🧹 Clear All Data</button>' +
        '</div>' +
      '</div>';
    document.body.appendChild(cp);

    /* Tracker panel */
    var tp = document.createElement('div');
    tp.id = 'cfTrkPanel';
    tp.innerHTML =
      '<div id="cfTrkHdr">' +
        '<h2>' +
          '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13" rx="2"/><path d="M16 8h4l3 5v3h-7V8z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>' +
          ' Order Tracker' +
        '</h2>' +
        '<div id="cfTrkHdrR">' +
          '<button id="cfRcptBtn">&#x1F9FE; Receipt</button>' +
          '<button class="cfClose" id="cfTrkClose" style="background:rgba(255,255,255,.18);border:1.5px solid rgba(255,255,255,.4);color:#fff;width:30px;height:30px;border-radius:50%;font-size:16px;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:background .2s">&#x2715;</button>' +
        '</div>' +
      '</div>' +
      '<div id="cfTrkBody">' +
        /* Dark banner: left = order id, right = timer/status */
        '<div id="cfTrkBanner">' +
          '<div><div class="cfBanLbl">ORDER</div><div class="cfBanOid" id="cfTOid">-</div></div>' +
          '<div class="cfBanR"><div class="cfEtaN" id="cfEtaN">--</div><div class="cfEtaL" id="cfEtaL">est. wait</div></div>' +
        '</div>' +
        /* White progress card */
        '<div id="cfProgCard">' +
          '<div id="cfProgTitle">ORDER PROGRESS</div>' +
          '<div id="cfProgSteps"><div id="cfProgFill"></div></div>' +
        '</div>' +
        /* Status card — icon + title/sub */
        '<div id="cfStCard">' +
          '<div id="cfStIcon">' +
            '<svg viewBox="0 0 24 24"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="1"/><path d="M9 12h6M9 16h4"/></svg>' +
          '</div>' +
          '<div><div id="cfStTitle">Received</div><div id="cfStSub">Order received at the counter!</div></div>' +
        '</div>' +
        /* Items ordered card */
        '<div id="cfTrkItemsCard"></div>' +
        /* Seat strip */
        '<div id="cfTrkSeat"></div>' +
        /* Delivery confirmation card — shown only when countdown hits 0 */
        '<div id="cfDeliveryConfirm" style="display:none;flex-direction:column;align-items:center;gap:12px;background:linear-gradient(135deg,rgba(38,222,129,.15),rgba(38,222,129,.05));border:2px solid rgba(38,222,129,.5);border-radius:14px;padding:20px 18px;text-align:center;margin-top:4px">' +
          '<div style="font-size:2rem">🛸</div>' +
          '<div style="font-weight:700;font-size:.95rem;color:#26de81">Your order should be at your seat now!</div>' +
          '<div style="font-size:.82rem;color:#aaa">Has your food arrived at your seat?</div>' +
          '<div style="display:flex;gap:10px;width:100%">' +
            '<button id="cfConfirmYes" style="flex:1;padding:10px;background:#26de81;border:none;border-radius:9px;color:#111;font-weight:700;font-family:Poppins,sans-serif;font-size:.88rem;cursor:pointer;transition:all .2s">✅ Yes, it arrived!</button>' +
            '<button id="cfConfirmNo" style="flex:1;padding:10px;background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.2);border-radius:9px;color:#fff;font-weight:600;font-family:Poppins,sans-serif;font-size:.88rem;cursor:pointer;transition:all .2s">⏳ Not yet</button>' +
          '</div>' +
        '</div>' +
      '</div>';
    document.body.appendChild(tp);
  }

  buildDOM();

  /* ── HELPERS ──────────────────────────────────────────────────────────── */
  var g = function (id) { return document.getElementById(id); };

  function showFlex(id) {
    var el = g(id);
    if (!el) return;
    el.style.display = 'flex';
    el.style.flexDirection = 'column';
  }
  function hideEl(id) {
    var el = g(id);
    if (el) el.style.display = 'none';
  }

  /* ── SIMPLE LOCALSTORAGE INTEGRATION ─────────────────────────────────── */
  
  // Save cart to localStorage
  function saveCart() {
    try {
      localStorage.setItem('cineflix_cart', JSON.stringify({
        cart: cart,
        discountApplied: discountApplied,
        lockedOid: lockedOid,
        timestamp: Date.now()
      }));
    } catch (e) {
      console.log('Could not save cart');
    }
  }

  // Load cart from localStorage
  function loadCart() {
    try {
      var saved = localStorage.getItem('cineflix_cart');
      if (saved) {
        var data = JSON.parse(saved);
        // Only load if less than 24 hours old
        if (Date.now() - data.timestamp < 24 * 60 * 60 * 1000) {
          cart = data.cart || [];
          discountApplied = data.discountApplied || false;
          lockedOid = data.lockedOid || null;
          return true;
        }
      }
    } catch (e) {
      console.log('Could not load cart');
    }
    return false;
  }

  /* ── STATE ────────────────────────────────────────────────────────────── */
  var cart         = [];
  var discountApplied = false;
  var lockedOid    = null;
  var trkIv        = null;
  var cntIv        = null;
  var trkEstMins   = 10;
  var trkStartMs   = null;
  var countdownSeconds = 10 * 60; // 12 minutes real countdown

  // Load cart on startup AFTER variables are declared
  loadCart();

  // Countdown timer — MM:SS display, drives stage advances
  // Stages advance at: 0→1 at 9:00 left, 1→2 at 6:00, 2→3 at 3:00, 3→4 at 0:00
  var STAGE_THRESHOLDS = [7*60+30, 5*60, 2*60+30, 0]; // advance at 7:30 / 5:00 / 2:30 / 0

  function startCountdown() {
    if (cntIv) clearInterval(cntIv);
    var stagesAdvanced = {};

    cntIv = setInterval(function() {
      if (countdownSeconds > 0) {
        countdownSeconds--;

        var minutes = Math.floor(countdownSeconds / 60);
        var seconds = countdownSeconds % 60;
        var timeString = minutes + ':' + (seconds < 10 ? '0' : '') + seconds;

        var ne = g('cfEtaN');
        var le = g('cfEtaL');
        if (ne) ne.textContent = timeString;
        if (le) le.textContent = 'est. wait';

        // Advance stages at threshold points
        STAGE_THRESHOLDS.forEach(function(thresh, idx) {
          var targetStage = idx + 1; // stages 1,2,3,4
          if (!stagesAdvanced[targetStage] && countdownSeconds <= thresh && targetStage < STAGES.length) {
            stagesAdvanced[targetStage] = true;
            simStageIndex = targetStage;
            var fakeOrder = {
              orderId: simOid || lockedOid,
              status: targetStage >= STAGES.length - 1 ? 'delivered' : 'in_progress',
              currentStageIndex: targetStage,
              remainingMinutes: Math.ceil(countdownSeconds / 60),
              items: null, totalAmount: null, seatNumber: null
            };
            renderTrack(fakeOrder);
          }
        });

        if (countdownSeconds === 0) {
          clearInterval(cntIv);
          cntIv = null;
          showDeliveryConfirm();
        }
      } else {
        clearInterval(cntIv);
        cntIv = null;
      }
    }, 1000);
  }

  // Simple test function for cart persistence
  window.testCart = function() {
    console.log('Cart test - Current cart:', cart);
    console.log('localStorage data:', localStorage.getItem('cineflix_cart'));
    return { cart: cart, stored: localStorage.getItem('cineflix_cart') };
  };

  // Direct localStorage test
  window.testStorage = function() {
    console.log('Testing localStorage directly...');
    
    // Test saving
    var testData = { test: 'hello', timestamp: Date.now() };
    localStorage.setItem('test_key', JSON.stringify(testData));
    console.log('Saved test data:', testData);
    
    // Test loading
    var loaded = localStorage.getItem('test_key');
    console.log('Loaded test data:', loaded);
    
    if (loaded) {
      var parsed = JSON.parse(loaded);
      console.log('Parsed test data:', parsed);
      console.log('✅ localStorage is working!');
    } else {
      console.log('❌ localStorage failed!');
    }
    
    // Clean up
    localStorage.removeItem('test_key');
    
    return loaded;
  };

  // Force save cart test
  window.forceSaveCart = function() {
    console.log('Force saving cart...');
    cart = [{id: 'test', name: 'Test Item', price: 100, qty: 1}];
    saveCart();
    console.log('Cart forced:', cart);
    console.log('localStorage after force save:', localStorage.getItem('cineflix_cart'));
  };

  /* ── PANELS ───────────────────────────────────────────────────────────── */
  function openCart()  {
    // Restore cart from localStorage
    loadCart();
    syncCart();
    
    if (!discountApplied) {
      var ab = g('cfDiscApplyBtn'); var dm = g('cfDiscMsg'); var di = g('cfDiscInp');
      if (ab) { ab.textContent = 'Apply'; ab.style.background = 'linear-gradient(135deg,#c79f5e,#d98639)'; ab.disabled = false; }
      if (dm) { dm.style.color = '#999'; dm.textContent = ''; }
      if (di) { di.value = ''; di.disabled = false; di.style.borderColor = '#ddd'; di.style.background = '#fff'; }
    }
    showFlex('cfCartPanel'); showFlex('cfOverlay');
  }
  function closeCart() { hideEl('cfCartPanel'); chkOverlay(); }
  var simStageIndex = 0;
  var simIv = null;
  var simOid = null;

  function startStageSimulation(oid, startStage) {
    // Stage advancement is now driven by the countdown timer (STAGE_THRESHOLDS)
    simOid = oid;
    simStageIndex = startStage || 0;
    if (simIv) { clearInterval(simIv); simIv = null; }
  }

  function openTrack(oid) {
    if (!oid) return;
    lockedOid = oid;
    saveCart();
    var tp = g('cfTrkPanel'); if (!tp) return;
    tp.dataset.oid = oid;
    // Entrance animation
    tp.style.transform = 'translateX(100%)';
    tp.style.opacity   = '0';
    showFlex('cfOverlay'); showFlex('cfTrkPanel');
    requestAnimationFrame(function() {
      tp.style.transition = 'transform .45s cubic-bezier(.4,0,.2,1), opacity .35s ease';
      tp.style.transform  = 'translateX(0)';
      tp.style.opacity    = '1';
    });
    fetchTrack(oid);
  }

  function closeTrack() {
    hideEl('cfTrkPanel'); hideEl('cfOverlay');
    if (trkIv) { clearInterval(trkIv); trkIv = null; }
    if (cntIv) { clearInterval(cntIv); cntIv = null; }
    // Don't clear lockedOid on close - keep it for tracking persistence
  }

  function chkOverlay() {
    var cp = g('cfCartPanel'); var tp = g('cfTrkPanel');
    if ((!cp || cp.style.display === 'none') && (!tp || tp.style.display === 'none')) {
      hideEl('cfOverlay');
    }
  }

  /* ── TRACKING STATE MANAGEMENT ─────────────────────────────────────────── */
  function clearTrackingData() {
    lockedOid = null;
    trkStartMs = null;
    trkEstMins = 10;
    if (trkIv) { clearInterval(trkIv); trkIv = null; }
    if (cntIv) { clearInterval(cntIv); cntIv = null; }
    saveCart();
  }

  function showDeliveryConfirm() {
    var card = g('cfDeliveryConfirm');
    if (!card) return;
    // Slide it in
    card.style.display = 'flex';
    card.style.opacity = '0';
    card.style.transform = 'translateY(16px)';
    card.style.transition = 'all .4s cubic-bezier(.4,0,.2,1)';
    requestAnimationFrame(function() {
      card.style.opacity   = '1';
      card.style.transform = 'translateY(0)';
    });
    // Also update banner to show a pulsing "Arrived?" message
    var ne = g('cfEtaN'); var le = g('cfEtaL');
    if (ne) { ne.textContent = '?'; ne.style.color = '#26de81'; }
    if (le) le.textContent = 'Check your seat!';

    var yesBtn = g('cfConfirmYes');
    var noBtn  = g('cfConfirmNo');
    if (yesBtn) {
      yesBtn.onclick = function() {
        card.style.opacity = '0';
        card.style.transform = 'translateY(-10px)';
        setTimeout(function() { card.style.display = 'none'; }, 350);
        markOrderAsDelivered();
      };
    }
    if (noBtn) {
      noBtn.onclick = function() {
        // Give 2 more minutes and ask again
        noBtn.textContent = '⏳ Waiting...';
        noBtn.disabled = true;
        countdownSeconds = 2 * 60;
        card.style.opacity = '0';
        card.style.transform = 'translateY(-10px)';
        setTimeout(function() {
          card.style.display = 'none';
          card.style.opacity = '';
          card.style.transform = '';
          // Restart countdown for 2 more minutes
          var ne2 = g('cfEtaN'); var le2 = g('cfEtaL');
          if (ne2) { ne2.textContent = '2:00'; ne2.style.color = ''; }
          if (le2) le2.textContent = 'est. wait';
          if (noBtn) { noBtn.textContent = '⏳ Not yet'; noBtn.disabled = false; }
          startCountdown();
        }, 350);
      };
    }
  }

  // Mark order as delivered when user clicks "Received" button
  function markOrderAsDelivered() {
    if (!lockedOid) return;
    
    // Stop the countdown timer
    if (cntIv) {
      clearInterval(cntIv);
      cntIv = null;
    }
    
    // Update tracking panel to show delivered
    var deliveredOrder = {
      orderId: lockedOid,
      status: 'delivered',
      currentStageIndex: STAGES.length - 1,
      remainingMinutes: 0,
      items: null,
      seatNumber: null,
      totalAmount: 0
    };
    
    renderTrack(deliveredOrder);
    
    // Clear tracking data after delivery
    setTimeout(function() {
      clearTrackingData();
      addMsg('🎉 Enjoy your snacks! Thanks for ordering with CineFlix.', false, {
        chips: ['Order more food', 'Show food menu']
      });
    }, 2000);
  }

  function clearAllFoodData() {
    console.log('🧹 Clearing all food data...');
    cart = [];
    discountApplied = false;
    clearTrackingData();
    syncCart();
    saveCart(); // Use the new saveCart function
    
    // Show notification
    if (typeof showNotification === 'function') {
      showNotification('🧹 All food data cleared', 'info');
    }
    console.log('✅ All food data cleared');
  }
  function getSub()  { return cart.reduce(function (s, i) { return s + i.price * i.qty; }, 0); }
  function getDisc() { return discountApplied ? Math.round(getSub() * 0.20 * 100) / 100 : 0; }
  function getTot()  { return discountApplied ? Math.round(getSub() * 0.80 * 100) / 100 : getSub(); }

  function addToCart(id, name, price, qty) {
    var ex = null;
    for (var i = 0; i < cart.length; i++) {
      if (cart[i].id === id) {
        ex = i;
        break;
      }
    }
    if (ex !== null) {
      cart[ex].qty += qty;
    } else {
      cart.push({ id:id, name:name, price:price, qty:qty });
    }
    syncCart();
    saveCart();
  }

  function updateCart(id, qty) {
    for (var i = 0; i < cart.length; i++) {
      if (cart[i].id === id) {
        if (qty <= 0) {
          cart.splice(i, 1);
        } else {
          cart[i].qty = qty;
        }
        break;
      }
    }
    syncCart();
    saveCart();
  }

  function removeFromCart(id) {
    cart = cart.filter(function (i) { return i.id !== id; });
    syncCart();
    saveCart();
  }

  function clearCart() {
    console.log('🧹 Clearing cart...');
    // Only clear if user explicitly requests it (not on reload/logout)
    if (arguments.length === 0) {
      console.warn('⚠️ clearCart() called without user action - this might be accidental');
      // Don't clear on automatic calls
      return;
    }
    cart = [];
    discountApplied = false;
    syncCart();
    saveCart();
    console.log('✅ Cart cleared');
  }

  function syncCart() {
    try {
      console.log('🔄 Syncing cart UI. Current cart:', cart);
      var count = cart.reduce(function (s, i) { return s + i.qty; }, 0);
      var badge = g('cfBadge');
      if (badge) { 
        badge.textContent = count; 
        badge.style.display = count > 0 ? 'inline-flex' : 'none';
        console.log('🏷️ Badge updated:', {count, display: badge.style.display});
      } else {
        console.log('ℹ️ Badge element not found (chatbot may not be initialized yet)');
      }

    var empty  = cart.length === 0;
    var emEl   = g('cfCartEmpty');
    var itEl   = g('cfCartItems');
    var sumEl  = g('cfCartSum');
    var chkEl  = g('cfChkSec');
    var dscSec = g('cfDiscSec');
    var dscRow = g('cfDiscRow');

    console.log('🎯 UI elements found:', {
      emptyEl: !!emEl,
      itemsEl: !!itEl,
      sumEl: !!sumEl,
      chkEl: !!chkEl,
      dscSec: !!dscSec,
      dscRow: !!dscRow
    });

    if (emEl) emEl.style.display = empty ? 'flex'   : 'none';
    if (itEl) itEl.style.display = empty ? 'none'   : 'flex';
    if (sumEl) sumEl.style.display = empty ? 'none'   : 'flex';
    if (chkEl) chkEl.style.display = empty ? 'none'   : 'flex';
    if (dscSec) dscSec.style.display = empty ? 'none' : 'flex';
    if (dscRow) dscRow.style.display = (!empty && discountApplied) ? 'flex' : 'none';

    if (!empty && itEl) {
      console.log('🛒 Building cart items HTML...');
      itEl.innerHTML = '';
      cart.forEach(function (i) {
        var div = document.createElement('div');
        div.className = 'cfCI';
        div.innerHTML = 
          '<div class="cfCI1"><span class="cfCIName">' + i.name + '</span><span class="cfCITot">&#8369;' + (i.price * i.qty).toFixed(2) + '</span></div>' +
          '<div class="cfCI2">' +
          '<button class="cfCIQ" data-id="' + i.id + '" data-d="-1">-</button>' +
          '<span class="cfCIQty">' + i.qty + '</span>' +
          '<button class="cfCIQ" data-id="' + i.id + '" data-d="1">+</button>' +
          '<span class="cfCIUnit">x &#8369;' + i.price.toFixed(2) + '</span>' +
          '<button class="cfCIRm" data-id="' + i.id + '">🗑</button>' +
          '</div>';
        itEl.appendChild(div);
      });
      itEl.querySelectorAll('.cfCIQ').forEach(function (btn) {
        btn.addEventListener('click', function () {
          var item = null;
          for (var i = 0; i < cart.length; i++) { if (cart[i].id === btn.dataset.id) { item = cart[i]; break; } }
          if (!item) return;
          item.qty += parseInt(btn.dataset.d, 10);
          if (item.qty <= 0) cart = cart.filter(function (i) { return i.id !== btn.dataset.id; });
          syncCart();
        });
      });
      itEl.querySelectorAll('.cfCIRm').forEach(function (btn) {
        btn.addEventListener('click', function () {
          cart = cart.filter(function (i) { return i.id !== btn.dataset.id; });
          syncCart();
        });
      });
    }

    var se = g('cfSub'); var de = g('cfDisc'); var te = g('cfTot');
    if (se) se.textContent = '&#8369;' + getSub().toFixed(2);
    if (de) de.textContent = '-&#8369;' + getDisc().toFixed(2);
    if (te) te.textContent = '&#8369;' + getTot().toFixed(2);

    // Update totals properly (textContent doesn't parse HTML entities)
    if (se) se.innerHTML = '&#8369;' + getSub().toFixed(2);
    if (de) de.innerHTML = '-&#8369;' + getDisc().toFixed(2);
    if (te) te.innerHTML = '&#8369;' + getTot().toFixed(2);
    
    console.log('💰 Totals updated:', {sub: getSub(), disc: getDisc(), tot: getTot()});
    } catch (error) {
      console.error('❌ Error in syncCart:', error);
    }
  }

  /* ── PLACE ORDER ──────────────────────────────────────────────────────── */

  /* Helper: set Place Order button state (simple loading only) */
  function setBtnState(btn, drone, state, label) {
    if (!btn) return;
    btn.className = 'cfPlaceBtn';
    btn.innerHTML = label;
    btn.disabled  = (state !== '');
    btn.style.width = '100%';
    if (state === 'cf-btn-processing') {
      btn.classList.add('cf-btn-processing');
    }
    // Hide drone always — no drone animation in cart
    if (drone) {
      drone.style.animation = 'none';
      drone.style.opacity   = '0';
      drone.style.bottom    = '0';
    }
  }

  async function placeOrder(items, seat, subtotal) {
    var btn   = g('cfPlaceBtn');
    var drone = g('cfDroneIcon');
    trackBtnReady = false; // lock Track Order until animation completes

    /* ── Stage 1: Processing ── */
    setBtnState(btn, drone, 'cf-btn-processing', '⏳ Processing ..');

    var res, data;
    try {
      res = await fetch(API, {
        method:  'POST',
        headers: { 'Content-Type': 'application/json' },
        body:    JSON.stringify({
          action: 'order',
          items: items,
          seat: seat,
          total: subtotal,
          pwdSeniorId: discountApplied ? ((g('cfDiscInp') ? g('cfDiscInp').value.trim() : '') || '') : ''
        })
      });
      var rawText = await res.text();
      console.log('Order response:', rawText);
      try { data = JSON.parse(rawText); }
      catch (pe) {
        setBtnState(btn, drone, '', '🛒 Place Order');
        btn.disabled = false;
        addMsg('Server error: ' + rawText.substring(0, 150), false);
        return;
      }
    } catch (e) {
      setBtnState(btn, drone, '', '🛒 Place Order');
      btn.disabled = false;
      addMsg('Connection error: ' + e.message, false);
      console.error('placeOrder error:', e);
      return;
    }

    if (data.error === 'login_required') {
      setBtnState(btn, drone, '', '🛒 Place Order');
      btn.disabled = false;
      closeCart();
      addMsg('You need to be logged in to place a food order. Please log in first!', false, { chips: ['Go to login page'] });
      return;
    }

    if (!data.success) {
      setBtnState(btn, drone, '', '🛒 Place Order');
      btn.disabled = false;
      addMsg('Failed: ' + (data.error || 'Unknown error'), false);
      return;
    }

    /* ── Order successful — finalize immediately ── */
    lockedOid        = data.trackOrderId;
    trkStartMs       = Date.now();
    trkEstMins       = 10;
    countdownSeconds = 10 * 60;

    saveCart();
    startCountdown();
    closeCart();

    var panel = g('cfPanel');
    if (panel) panel.style.display = 'flex';
    showOrderConfirm(items, subtotal, data.trackOrderId, data.finalTotal);

    cart = [];
    discountApplied = false;
    var ab2 = g('cfDiscApplyBtn'); var dm2 = g('cfDiscMsg'); var di2 = g('cfDiscInp');
    if (ab2) { ab2.textContent = 'Apply'; ab2.style.background = 'linear-gradient(135deg,#c79f5e,#d98639)'; ab2.disabled = false; }
    if (dm2) { dm2.style.color = '#999'; dm2.textContent = ''; }
    if (di2) { di2.value = ''; di2.disabled = false; di2.style.borderColor = '#ddd'; di2.style.background = '#fff'; }

    saveCart();
    syncCart();

    /* Track Order button is already enabled when showOrderConfirm() created it */
    trackBtnReady = true;

    /* Reset Place Order button */
    setBtnState(btn, drone, '', '🛒 Place Order');
    btn.disabled = false;
  }

  /* ── MESSAGES ─────────────────────────────────────────────────────────── */
  function addMsg(text, isUser, opts) {
    opts = opts || {};
    var msgs = g('cfMsgs'); if (!msgs) return;
    var div = document.createElement('div');
    div.className = 'cfMsg ' + (isUser ? 'u' : 'b');
    if (opts.confirm) div.className += ' ok';
    div.innerHTML = text
      .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
      .replace(/\n/g, '<br>');

    if (opts.chips && opts.chips.length) {
      var row = document.createElement('div'); row.className = 'cfChips';
      opts.chips.forEach(function (label) {
        var b = document.createElement('button'); b.className = 'cfChip'; b.textContent = label;
        b.addEventListener('click', function () { doInput(label); });
        row.appendChild(b);
      });
      div.appendChild(row);
    }

    if (opts.showMenu) {
      var wrap = document.createElement('div'); wrap.className = 'cfMenuWrap';
      MENU.forEach(function (item) {
        var mrow = document.createElement('div'); mrow.className = 'cfMRow';
        mrow.innerHTML =
          '<span class="cfMName">' + item.emoji + ' ' + item.name +
          '<span class="cfMPrice">&#8369;' + item.price.toFixed(2) + '</span></span>' +
          '<div class="cfQCtrl">' +
          '<button class="cfQB" data-id="' + item.id + '" data-d="-1">-</button>' +
          '<span class="cfQV" data-qid="' + item.id + '">0</span>' +
          '<button class="cfQB" data-id="' + item.id + '" data-d="1">+</button>' +
          '</div>';
        wrap.appendChild(mrow);
      });
      wrap.querySelectorAll('.cfQB').forEach(function (btn) {
        btn.addEventListener('click', function () {
          var el = wrap.querySelector('[data-qid="' + btn.dataset.id + '"]');
          if (el) el.textContent = Math.max(0, parseInt(el.textContent, 10) + parseInt(btn.dataset.d, 10));
        });
      });
      var addBtn = document.createElement('button'); addBtn.className = 'cfAddBtn'; addBtn.textContent = '🛒 Add to Cart';
      addBtn.addEventListener('click', function () {
        var added = false;
        MENU.forEach(function (item) {
          var el  = wrap.querySelector('[data-qid="' + item.id + '"]');
          var qty = el ? parseInt(el.textContent, 10) : 0;
          if (qty > 0) { addToCart(item.id, item.name, item.price, qty); added = true; }
        });
        if (added) {
          addBtn.textContent = 'Added!'; addBtn.disabled = true; addBtn.style.background = '#26de81';
          addMsg(
            'Added to your cart!\n\n' +
            cart.map(function (i) { return '**' + i.name + '** x' + i.qty + ' - P' + (i.price * i.qty).toFixed(2); }).join('\n') +
            '\n\nSubtotal: P' + getSub().toFixed(2) + ' (PWD/Senior discount available at checkout)',
            false, { chips: ['View cart', 'Add more food'] }
          );
        } else {
          addMsg('Please select a quantity first.', false);
        }
      });
      wrap.appendChild(addBtn); div.appendChild(wrap);
    }

    if (opts.trackId) {
      var tb = document.createElement('button'); tb.className = 'cfTBtn'; tb.textContent = '📦 Track Order';
      tb.addEventListener('click', function () { openTrack(opts.trackId); }); div.appendChild(tb);
    }

    /* ── Mood movie cards ── */
    if (opts.moodMovies && opts.moodMovies.length) {
      var moodWrap = document.createElement('div');
      moodWrap.style.cssText = 'display:flex;flex-direction:column;gap:8px;margin-top:10px;';
      opts.moodMovies.forEach(function (movie) {
        var card = document.createElement('div');
        card.style.cssText = 'background:rgba(199,159,94,.12);border:1px solid rgba(199,159,94,.3);border-radius:10px;padding:10px 12px;cursor:pointer;transition:background .2s;';
        var worthBar = Math.round(movie.worthScore / 10);
        var barFull  = '█'.repeat(worthBar) + '░'.repeat(10 - worthBar);
        card.innerHTML =
          '<div style="display:flex;justify-content:space-between;align-items:center;">' +
            '<strong style="color:#f0d08a;font-size:.88rem;">' + movie.title + '</strong>' +
            '<span style="color:#c79f5e;font-size:.78rem;">⭐ ' + movie.rating + '</span>' +
          '</div>' +
          '<div style="color:rgba(255,255,255,.6);font-size:.75rem;margin:3px 0;">' + movie.vibes + '</div>' +
          '<div style="color:#c79f5e;font-size:.7rem;font-family:monospace;letter-spacing:-1px;">' + barFull + ' ' + movie.worthScore + '%</div>';
        card.addEventListener('mouseenter', function () { card.style.background = 'rgba(199,159,94,.22)'; });
        card.addEventListener('mouseleave', function () { card.style.background = 'rgba(199,159,94,.12)'; });
        card.addEventListener('click', function () { doInput('Review: ' + movie.title); });
        moodWrap.appendChild(card);
      });
      div.appendChild(moodWrap);
    }

    /* ── Worth-It score bar ── */
    if (opts.worthScore != null) {
      var wsWrap = document.createElement('div');
      wsWrap.style.cssText = 'margin-top:10px;padding:10px 12px;background:rgba(255,255,255,.05);border-radius:8px;border:1px solid rgba(255,255,255,.1);';
      var pct    = opts.worthScore;
      var wColor = pct >= 90 ? '#26de81' : (pct >= 75 ? '#c79f5e' : '#f97373');
      wsWrap.innerHTML =
        '<div style="display:flex;justify-content:space-between;margin-bottom:6px;">' +
          '<span style="font-size:.75rem;color:rgba(255,255,255,.5);text-transform:uppercase;letter-spacing:.06em;">Worth It Score</span>' +
          '<strong style="color:' + wColor + ';">' + pct + '%</strong>' +
        '</div>' +
        '<div style="height:6px;background:rgba(255,255,255,.1);border-radius:3px;overflow:hidden;">' +
          '<div style="height:100%;width:0%;background:linear-gradient(90deg,' + wColor + ',#c79f5e);border-radius:3px;transition:width 1s ease;" data-target="' + pct + '"></div>' +
        '</div>';
      div.appendChild(wsWrap);
      // Animate bar after render
      setTimeout(function () {
        var fill = wsWrap.querySelector('[data-target]');
        if (fill) fill.style.width = fill.dataset.target + '%';
      }, 100);
    }

    /* ── Timing card ── */
    if (opts.timingCard) {
      var tc = opts.timingCard;
      var tWrap = document.createElement('div');
      tWrap.style.cssText = 'margin-top:10px;background:rgba(26,26,46,.9);border:1px solid rgba(199,159,94,.35);border-radius:10px;overflow:hidden;';
      tWrap.innerHTML =
        '<div style="background:linear-gradient(135deg,#c79f5e,#d98639);padding:8px 14px;font-size:.76rem;font-weight:700;color:#fff;letter-spacing:.04em;">⏰ YOUR CINEMA TIMELINE</div>' +
        '<div style="padding:12px 14px;display:flex;flex-direction:column;gap:6px;">' +
          '<div style="display:flex;justify-content:space-between;font-size:.83rem;">' +
            '<span style="color:rgba(255,255,255,.5);">🏠 Leave home</span><strong style="color:#f0d08a;">' + tc.leaveBy + '</strong>' +
          '</div>' +
          '<div style="display:flex;justify-content:space-between;font-size:.83rem;">' +
            '<span style="color:rgba(255,255,255,.5);">🎟️ Arrive at cinema</span><strong style="color:#f0d08a;">' + tc.arriveBy + '</strong>' +
          '</div>' +
          '<div style="height:1px;background:rgba(255,255,255,.08);margin:2px 0;"></div>' +
          '<div style="display:flex;justify-content:space-between;font-size:.83rem;">' +
            '<span style="color:rgba(255,255,255,.5);">🎬 Movie starts</span><strong style="color:#26de81;">' + tc.showStart + '</strong>' +
          '</div>' +
          '<div style="display:flex;justify-content:space-between;font-size:.83rem;">' +
            '<span style="color:rgba(255,255,255,.5);">🏁 Ends</span><strong style="color:rgba(255,255,255,.7);">' + tc.showEnd + '</strong>' +
          '</div>' +
        '</div>';
      div.appendChild(tWrap);
    }

    msgs.appendChild(div); msgs.scrollTop = msgs.scrollHeight; return div;
  }

  var trackBtnReady = false; // becomes true after place-order animation finishes

  function showOrderConfirm(items, subtotal, trackId, finalTotal) {
    var disc = discountApplied ? Math.round(subtotal * 0.20 * 100) / 100 : 0;
    var fTot = (finalTotal != null) ? finalTotal : (discountApplied ? Math.round(subtotal * 0.80 * 100) / 100 : subtotal);
    var msgs = g('cfMsgs'); if (!msgs) return;
    var div  = document.createElement('div'); div.className = 'cfMsg b ok';

    div.innerHTML =
      '<strong>🎉 Order Confirmed!</strong><br>' +
      'Your order has been sent to our in-cinema food counter. It will be brought to your seat in about <strong>10 minutes</strong>.<br><br>' +
      '<span style="font-size:.8rem;color:rgba(255,255,255,.6);">Tap <strong>Track Order</strong> below to watch the live progress and timer.</span>';

    var row = document.createElement('div'); row.className = 'cfActRow';
    var tb  = document.createElement('button');
    tb.className = 'cfTBtn';
    tb.id = 'cfTrackOrderBtn';
    tb.style.cssText = 'opacity:1;cursor:pointer;';
    tb.textContent = '📦 Track Order';
    tb.disabled = false;
    tb.addEventListener('click', function () {
      openTrack(trackId);
    });
    row.appendChild(tb); div.appendChild(row);
    msgs.appendChild(div); msgs.scrollTop = msgs.scrollHeight;
  }

  function showMenu() {
    addMsg('Here is our menu!\nPWD/Senior Citizen 20% discount available at checkout.\nPick your items:', false, { showMenu: true });
  }

  /* ── HANDLE INPUT ─────────────────────────────────────────────────────── */
  async function doInput(text) {
    text = (text || '').trim(); if (!text) return;
    var inp = g('cfInput'); if (inp) inp.value = '';
    var lower = text.toLowerCase();

    /* Silent actions */
    if (/view cart|my cart|show cart|open cart/i.test(lower))         { openCart();  return; }
    if (/go to login|go to login page/i.test(lower))                  { window.location.href = LOGIN_URL; return; }
    /* Group seating chip — reroute to group booking */
    if (/^group seat(ing)?$|^plan group booking$|^group booking( help)?$/i.test(lower)) {
      addMsg(text, true);
      // Fall through to server — but rewrite text so PHP hits Group Booking branch
      text = 'Group booking help'; lower = text.toLowerCase();
    }
    /* "N people" chips from group size prompt — forward to server as group booking */
    if (/^\d+\+?\s*(people|persons?)$/i.test(lower)) {
      addMsg(text, true);
      // Let server handle it; PHP Group Booking catches "N people"
      try {
        var resG = await fetch(API, { method: 'POST', headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ message: text, context: {}, action: 'chat' }) });
        var rawG = await resG.text();
        var dataG; try { dataG = JSON.parse(rawG); } catch(e){ addMsg('Server error', false); return; }
        if (!dataG.success) { addMsg('Something went wrong.', false); return; }
        var rG = dataG.response;
        addMsg(rG.content || 'How can I help?', false, { chips: rG.suggestions || [] });
      } catch(e) { addMsg('Connection error: ' + e.message, false); }
      return;
    }
    if (/^track$|track my order|track.*order/i.test(lower)) {
      if (lockedOid) { openTrack(lockedOid); return; }
      addMsg('You do not have an active order yet. Order some food first!', false, { chips: ['Show food menu'] }); return;
    }
    if (/^(show food menu|order food|food menu|show menu|add more food|order more)$/i.test(lower)) { showMenu(); return; }
    /* Mood shortcut chips like "😄 Happy" */
    if (/^(😄|😤|🤔|😎|😌|👨‍👩‍👧|💥|🧠)\s*/u.test(text)) {
      var moodWord = text.replace(/^[^\w]+/u, '').trim();
      doInput('I am feeling ' + moodWord); return;
    }

    /* ── Seat-intent quick actions (highlights seats on the booking grid) ── */
    const hasSeatIntent =
      /(best seats|best seat|smart seat|seat recommendations|seat guide|best view|viewing angle|maximum comfort|aisle access|date night|couple|family|corporate|accessibility|pwd|senior|similar seats|show similar)/i.test(lower);

    if (hasSeatIntent && window.CineFlixSeatTracker && document.getElementById('seats-grid')) {
      // Parse group size if mentioned
      let groupSize = null;
      let gm = lower.match(/(\d+)\s*(?:\+?\s*)?(people|persons|pax)\b/i);
      if (gm) groupSize = parseInt(gm[1], 10);

      if (!groupSize) {
        if (/(couple|date night|romantic)/i.test(lower)) groupSize = 2;
        else if (/(family)/i.test(lower)) groupSize = 4;
        else if (/(corporate|event|company)/i.test(lower)) groupSize = 6;
        else groupSize = (typeof bookingData !== 'undefined' && bookingData && bookingData.seats && bookingData.seats.length) ? bookingData.seats.length : 1;
      }

      let requestedMode = 'auto';
      if (/(couple|date night|romantic)/i.test(lower)) requestedMode = 'dating';
      else if (/(family)/i.test(lower)) requestedMode = 'family';
      else if (/(corporate|event|company)/i.test(lower)) requestedMode = 'corporate';
      else if (/(pwd|senior|elderly|wheelchair|accessibility|access)/i.test(lower)) requestedMode = 'accessibility';

      // Multi-intent: if user also asks for food, show menu after highlighting
      const wantsFood = /(order food|food menu|popcorn|snack|add food|drinks? menu)/i.test(lower);

      addMsg(text, true);
      try {
        window.CineFlixSeatTracker.highlightRecommendedSeatsForChat(requestedMode, groupSize);
        addMsg(
          `I highlighted the best available seat cluster for your request (${groupSize} seat${groupSize === 1 ? '' : 's'}).`,
          false,
          { chips: wantsFood ? ['Show food menu'] : ['Best seats?', 'Show food menu'] }
        );
        if (wantsFood) showMenu();
      } catch (e) {
        addMsg('I could not highlight seats right now. Please try again.', false);
      }
      return;
    }

    /* Quantity chips client-side */
    if (!lockedOid) {
      var matched = false;
      MENU.forEach(function (item) {
        var re = new RegExp('(\\d+)\\s*' + item.name.split(' ')[0], 'i');
        var m  = text.match(re);
        if (m) { addToCart(item.id, item.name, item.price, parseInt(m[1], 10)); matched = true; }
      });
      if (matched) {
        addMsg(text, true);
        addMsg(
          'Added to your cart!\n\n' +
          cart.map(function (i) { return '**' + i.name + '** x' + i.qty + ' - P' + (i.price * i.qty).toFixed(2); }).join('\n') +
          '\n\nSubtotal: P' + getSub().toFixed(2) + ' (PWD/Senior discount available at checkout)',
          false, { chips: ['View cart', 'Add more food'] }
        );
        return;
      }
    }

    /* Server call */
    addMsg(text, true);
    try {
      var res     = await fetch(API, {
        method:  'POST',
        headers: { 'Content-Type': 'application/json' },
        body:    JSON.stringify({ message: text, context: {}, action: 'chat' })
      });
      var rawChat = await res.text();
      console.log('Chat response:', rawChat);
      var data;
      try { data = JSON.parse(rawChat); }
      catch (pe) { addMsg('Server error - open console (F12) for details.', false); console.error('Raw:', rawChat); return; }
      if (!data.success) { addMsg('Something went wrong.', false); return; }
      var r = data.response;
      if (r.menu && r.menu.length && !lockedOid) { addMsg(r.content || 'Here is our menu:', false, { showMenu: true }); return; }
      if (r.type === 'order_confirm' && r.items && !lockedOid) {
        r.items.forEach(function (i) { addToCart(i.id, i.name, i.price, i.qty); });
        addMsg('Added to your cart!', false, { chips: ['View cart', 'Add more food'] }); return;
      }

      /* Rich opts for new feature types */
      var richOpts = { chips: r.suggestions || [], trackId: r.trackOrderId };
      if (r.type === 'mood_result' && r.movies)  richOpts.moodMovies = r.movies;
      if (r.type === 'review'      && r.movie)   richOpts.worthScore = r.movie.worthScore;
      if (r.type === 'worth_it'    && r.movie)   richOpts.worthScore = r.movie.worthScore;
      if (r.type === 'timing'      && r.timing)  richOpts.timingCard = r.timing;

      addMsg(r.content || 'How can I help?', false, richOpts);
    } catch (e) { addMsg('Connection error: ' + e.message, false); }
  }

  /* ── INIT ─────────────────────────────────────────────────────────────── */
  function initChat() {
    var mem = null;
    try { mem = JSON.parse(localStorage.getItem('cineflixSeatPrefs') || 'null'); } catch (e) { mem = null; }
    var memChip = mem && mem.pref ? 'Show similar seats' : null;
    var memLine = mem && mem.pref
      ? ('You previously seemed to prefer ' +
        (mem.pref.centerPref ? 'center-view seats' : 'easy comfort') +
        (mem.pref.aislePref ? ' with aisle access' : '') + '.')
      : '';

    addMsg(
      'Hi! I am your **CineFlix** assistant! 🎬\n\n' +
      '- 🎭 **Mood Match** — find films for your vibe\n' +
      '- 🪑 **Smart Seats** — best view, comfort & angle ratings\n' +
      '- 👥 **Group Booking** — plan & coordinate with friends\n' +
      '- ⏰ **Perfect Timing** — calculate when to leave\n' +
      '- ⭐ **Worth It?** — quick movie evaluation\n' +
      '- 🔍 **Spoiler-Free Reviews** — what to expect\n' +
      '- 🍿 **Food Ordering** — delivered to your seat\n\n' +
      (memLine ? (memLine + '\n\n') : '') +
      'What can I help you with?',
      false, { chips: memChip ? ['Match my mood 🎭', memChip, 'Best seats?', 'Show food menu'] : ['Match my mood 🎭', 'Best seats?', 'Is it worth it?', 'Show food menu'] }
    );
  }

  /* ── EVENTS ───────────────────────────────────────────────────────────── */
  g('cfToggle').addEventListener('click', function (e) {
    e.stopPropagation();
    var p = g('cfPanel'); if (!p) return;
    var open = (p.style.display === 'flex');
    p.style.display = open ? 'none' : 'flex';
    if (!open && g('cfMsgs').children.length === 0) initChat();
  });

  g('cfSendBtn').addEventListener('click', function () { doInput(g('cfInput').value); });
  g('cfInput').addEventListener('keypress', function (e) { if (e.key === 'Enter') doInput(g('cfInput').value); });
  g('cfCartIcon').addEventListener('click', function (e) { e.stopPropagation(); openCart(); });

  g('cfQuick').addEventListener('click', function (e) {
    e.stopPropagation();
    var btn = e.target.closest('.cfQBtn'); if (!btn) return;
    var a = btn.dataset.a;
    if (a === 'mood')   doInput('Match my mood');
    if (a === 'seats')  doInput('I need seat recommendations');
    if (a === 'worth')  doInput('Is it worth it?');
    if (a === 'timing') doInput('Help me with timing');
    if (a === 'group')  doInput('Group booking help');
    if (a === 'food')   showMenu();
    if (a === 'cart')   openCart();
    if (a === 'track')  { if (lockedOid) openTrack(lockedOid); else doInput('Track my order'); }
  });

  g('cfCartClose').addEventListener('click', function (e) { e.stopPropagation(); closeCart(); });
  g('cfTrkClose').addEventListener('click',  function (e) { e.stopPropagation(); closeTrack(); });
  g('cfOverlay').addEventListener('click',   function ()  { closeCart(); closeTrack(); });

  g('cfPlaceBtn').addEventListener('click', async function () {
    var si   = g('cfSeatInp');
    var seat = (si ? si.value : '').trim().toUpperCase();
    if (!seat || !/^[A-H]\d{1,2}$/.test(seat)) {
      if (si) { si.classList.add('cfshk'); si.value = ''; si.placeholder = 'e.g. D5'; setTimeout(function () { si.classList.remove('cfshk'); }, 500); }
      return;
    }
    if (cart.length === 0) return;
    await placeOrder(cart.slice(), seat, getSub());
  });

  /* PWD/Senior discount apply button */
  (function() {
    var applyBtn = g('cfDiscApplyBtn');
    if (!applyBtn) return;
    applyBtn.addEventListener('click', function () {
      var inp = g('cfDiscInp');
      var msgEl = g('cfDiscMsg');
      var val = (inp ? inp.value.trim() : '');
      /* Validate: must be at least 6 chars, letters/numbers/dashes only */
      if (!val || val.length < 6 || !/^[A-Za-z0-9\-\/\s]+$/.test(val)) {
        if (inp) {
          inp.style.borderColor = '#e53935';
          inp.style.background  = '#fff5f5';
          setTimeout(function () {
            inp.style.borderColor = '#ddd';
            inp.style.background  = '#fff';
          }, 1500);
        }
        if (msgEl) {
          msgEl.style.color = '#e53935';
          msgEl.textContent = '⚠️ Please enter a valid ID number (min. 6 characters).';
        }
        return;
      }
      
      /* Submit for admin approval instead of immediate approval */
      submitPwdDiscountRequest(val, inp, applyBtn, msgEl);
    });
    /* Allow pressing Enter in the input field */
    var inp = g('cfDiscInp');
    if (inp) {
      inp.addEventListener('keypress', function (e) {
        if (e.key === 'Enter') applyBtn.click();
      });
    }
  })();

  /* PWD Discount Approval System */
  async function submitPwdDiscountRequest(pwdId, inpEl, applyBtn, msgEl) {
    // Show loading state
    if (inpEl) {
      inpEl.disabled = true;
      inpEl.style.borderColor = '#ff9800';
      inpEl.style.background = '#fff8e1';
    }
    if (applyBtn) {
      applyBtn.textContent = '⏳ Pending...';
      applyBtn.style.background = '#ff9800';
      applyBtn.disabled = true;
    }
    if (msgEl) {
      msgEl.style.color = '#ff9800';
      msgEl.innerHTML = '🔄 Submitting PWD/Senior ID for admin approval...';
    }

    try {
      // Submit approval request to server
      var formData = new FormData();
      formData.append('action', 'request_pwd_discount');
      formData.append('pwdId', pwdId);
      formData.append('cartTotal', getSub());

      var response = await fetch(API, {
        method: 'POST',
        body: formData
      });

      var result = await response.json();
      
      if (result.success) {
        // Request submitted successfully
        if (msgEl) {
          msgEl.style.color = '#2196f3';
          msgEl.innerHTML = '⏳ <strong>Approval Pending</strong><br><small>Admin is reviewing your PWD/Senior ID. You\'ll be notified when approved.</small>';
        }
        if (applyBtn) {
          applyBtn.textContent = '⏳ Pending Approval';
          applyBtn.style.background = '#2196f3';
        }
        
        // Start checking for approval status
        startApprovalStatusCheck(pwdId, inpEl, applyBtn, msgEl);
      } else {
        // Request failed
        if (inpEl) {
          inpEl.disabled = false;
          inpEl.style.borderColor = '#e53935';
          inpEl.style.background = '#fff5f5';
        }
        if (applyBtn) {
          applyBtn.textContent = 'Apply';
          applyBtn.style.background = 'linear-gradient(135deg,#c79f5e,#d98639)';
          applyBtn.disabled = false;
        }
        if (msgEl) {
          msgEl.style.color = '#e53935';
          msgEl.innerHTML = '❌ <strong>Request Failed</strong><br><small>' + (result.error || 'Please try again.') + '</small>';
        }
      }
    } catch (error) {
      // Network error
      if (inpEl) {
        inpEl.disabled = false;
        inpEl.style.borderColor = '#e53935';
        inpEl.style.background = '#fff5f5';
      }
      if (applyBtn) {
        applyBtn.textContent = 'Apply';
        applyBtn.style.background = 'linear-gradient(135deg,#c79f5e,#d98639)';
        applyBtn.disabled = false;
      }
      if (msgEl) {
        msgEl.style.color = '#e53935';
        msgEl.innerHTML = '❌ <strong>Network Error</strong><br><small>Please check your connection and try again.</small>';
      }
    }
  }

  function startApprovalStatusCheck(pwdId, inpEl, applyBtn, msgEl) {
    var checkInterval = setInterval(async function() {
      try {
        var response = await fetch(API + '?action=check_pwd_approval&pwdId=' + encodeURIComponent(pwdId));
        var result = await response.json();
        
        if (result.success && result.approved) {
          // Approval granted!
          clearInterval(checkInterval);
          
          discountApplied = true;
          if (inpEl) {
            inpEl.disabled = true;
            inpEl.style.borderColor = '#26de81';
            inpEl.style.background = '#f0fff6';
          }
          if (applyBtn) {
            applyBtn.textContent = '✅ Approved';
            applyBtn.style.background = '#26de81';
          }
          if (msgEl) {
            msgEl.style.color = '#26de81';
            msgEl.innerHTML = '✅ <strong>Discount Approved!</strong><br><small>20% discount applied for ID: ' + pwdId + '</small>';
          }
          
          // Show notification
          if (typeof showNotification === 'function') {
            showNotification('🎉 Your PWD/Senior discount has been approved! 20% discount applied.', 'success');
          }
          
          syncCart();
          saveCart();
        } else if (result.success && result.rejected) {
          // Approval rejected
          clearInterval(checkInterval);
          
          if (inpEl) {
            inpEl.disabled = false;
            inpEl.style.borderColor = '#e53935';
            inpEl.style.background = '#fff5f5';
            inpEl.value = '';
          }
          if (applyBtn) {
            applyBtn.textContent = 'Apply';
            applyBtn.style.background = 'linear-gradient(135deg,#c79f5e,#d98639)';
            applyBtn.disabled = false;
          }
          if (msgEl) {
            msgEl.style.color = '#e53935';
            msgEl.innerHTML = '❌ <strong>Approval Rejected</strong><br><small>' + (result.reason || 'Invalid ID. Please contact support.') + '</small>';
          }
          
          // Show notification
          if (typeof showNotification === 'function') {
            showNotification('❌ Your PWD/Senior discount request was rejected. Please contact support for assistance.', 'error');
          }
        }
        // If still pending, continue checking
      } catch (error) {
        console.error('Approval status check error:', error);
      }
    }, 5000); // Check every 5 seconds
    
    // Stop checking after 5 minutes to prevent infinite polling
    setTimeout(function() {
      clearInterval(checkInterval);
    }, 300000);
  }

  g('cfRcptBtn').addEventListener('click', function () {
    var tp = g('cfTrkPanel');
    if (tp && tp.dataset.oid) dlReceipt(tp.dataset.oid);
  });

  // Delivery confirmation is now handled by showDeliveryConfirm() at countdown end

  // Clear all data button
  var clearBtn = g('cfClearBtn');
  if (clearBtn) {
    clearBtn.addEventListener('click', function() {
      if (confirm('Are you sure you want to clear all food data? This will remove your cart items and tracking information.')) {
        clearAllFoodData();
        closeCart();
      }
    });
  }

  /* ── TRACKER ──────────────────────────────────────────────────────────── */
  async function fetchTrack(oid) {
    try {
      var res  = await fetch(TRACK_API + '?orderId=' + encodeURIComponent(oid));
      var data = await res.json();
      if (!data.success) { console.warn('Track failed:', data); return; }
      var o = data.order;
      trkEstMins = o.estimatedMinutes || 10;
      // Use server-calculated remaining minutes as source of truth
      // Only set trkStartMs if we have a valid createdAt
      if (o.createdAt) {
        var parsed = new Date(o.createdAt).getTime();
        if (!isNaN(parsed)) trkStartMs = parsed;
      }
      // If still no start time, calculate from remaining minutes
      if (!trkStartMs && o.remainingMinutes > 0) {
        trkStartMs = Date.now() - ((trkEstMins - o.remainingMinutes) * 60000);
      }
      renderTrack(o);
      if (o.status !== 'delivered') startCountdown();
      /* Start local stage simulation only when order is first fetched (received stage) */
      if (simOid !== oid && o.currentStageIndex === 0 && o.status !== 'delivered') {
        startStageSimulation(oid, 0);
      }
    } catch (e) { console.error('Track error:', e); }
  }

  /* startCountdown is defined earlier and drives MM:SS display + stage advances */

  /* SVG paths for each stage icon (stroke-based, like reference image) */
  var STAGE_SVGS = [
    /* Order Placed — clipboard */
    '<svg viewBox="0 0 24 24"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="1"/><path d="M9 12h6M9 16h4"/></svg>',
    /* Preparing — box */
    '<svg viewBox="0 0 24 24"><path d="M21 8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/><path d="M3.27 6.96L12 12.01l8.73-5.05M12 22.08V12"/></svg>',
    /* Ready — bag */
    '<svg viewBox="0 0 24 24"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 01-8 0"/></svg>',
    /* On the Way — truck */
    '<svg viewBox="0 0 24 24"><rect x="1" y="3" width="15" height="13" rx="2"/><path d="M16 8h4l3 5v3h-7V8z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>',
    /* Delivered — check circle */
    '<svg viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><path d="M22 4L12 14.01l-3-3"/></svg>'
  ];
  var STAGES = [
    { label: 'Received',     sub: 'Order received at the counter!' },
    { label: 'Preparing',   sub: 'Our staff are preparing your order...' },
    { label: 'Ready',        sub: 'Your order is packed and ready!' },
    { label: 'On the Way',   sub: 'Staff member is heading to your seat...' },
    { label: 'Arrived',      sub: 'Enjoy your snacks! 🍿' }
  ];

  function renderTrack(o) {
    var ne = g('cfEtaN'); var le = g('cfEtaL'); var oe = g('cfTOid');
    var rem = (o.remainingMinutes != null) ? Math.max(0, o.remainingMinutes) : trkEstMins;

    if (o.status === 'delivered') {
      if (ne) ne.textContent = '✓';
      if (le) le.textContent = 'Arrived!';
      // Clear tracking data after delivery is confirmed
      setTimeout(function() {
        clearTrackingData();
        // Show completion message
        addMsg('🎉 Your order has arrived! Sit back and enjoy the show. 🍿', false, {
          chips: ['Order more food', 'Show food menu']
        });
      }, 2000);
      
      // Add celebration animation for delivered status
      var panel = g('cfTrkPanel');
      if (panel && !panel.classList.contains('delivered')) {
        panel.classList.add('delivered');
        celebrateDelivery();
      }
    } else {
      // Show live countdown from countdownSeconds (MM:SS format)
      var totalSeconds = countdownSeconds > 0 ? countdownSeconds : Math.max(0, rem * 60);
      var minutes = Math.floor(totalSeconds / 60);
      var seconds = totalSeconds % 60;
      var timeString = minutes + ':' + (seconds < 10 ? '0' : '') + seconds;

      if (ne) ne.textContent = timeString;
      if (le) le.textContent = 'est. wait';
      var panel = g('cfTrkPanel');
      if (panel) panel.classList.remove('delivered');
    }
    if (oe) oe.textContent = o.orderId;

    var stEl = g('cfProgSteps'); var fEl = g('cfProgFill');
    if (stEl) {
      // Clear existing steps with fade out animation
      var existingSteps = stEl.querySelectorAll('.cfStep');
      existingSteps.forEach(function(s, index) {
        setTimeout(function() {
          s.style.opacity = '0';
          s.style.transform = 'scale(0.8)';
          setTimeout(function() { s.remove(); }, 200);
        }, index * 50);
      });

      // Add new steps with staggered animation
      setTimeout(function() {
        STAGES.forEach(function (s, i) {
          // Show realistic progression - at least show "Order Placed" and "Preparing"
          var done = i < Math.max(1, o.currentStageIndex || 1);
          var cur = i === (o.currentStageIndex || 1);
          var isLast = i === STAGES.length - 1;
          var cls = 'cfStep ' + (done ? 'done' : (cur ? 'cur' : 'pend')) + (isLast ? ' cfDelivered' : '');
          var step = document.createElement('div');
          step.className = cls;
          step.style.opacity = '0';
          step.style.transform = 'scale(0.8) translateY(10px)';
          
          /* Always render SVG; done stages show a checkmark SVG */
          var iconSvg = done
            ? '<svg viewBox="0 0 24 24" style="width:26px;height:26px;stroke:#fff;fill:none;stroke-width:2.5;stroke-linecap:round;stroke-linejoin:round"><path d="M20 6L9 17l-5-5"/></svg>'
            : STAGE_SVGS[i];
          step.innerHTML = '<div class="cfStepDot">' + iconSvg + '</div>' +
                           '<div class="cfStepLbl">' + s.label + '</div>';
          stEl.appendChild(step);
          
          // Animate in with stagger
          setTimeout(function() {
            step.style.transition = 'all 0.4s cubic-bezier(0.4, 0, 0.2, 1)';
            step.style.opacity = '1';
            step.style.transform = 'scale(1) translateY(0)';
            
            // Add pulse animation for current step
            if (cur) {
              step.classList.add('pulse');
              setTimeout(function() {
                step.classList.remove('pulse');
              }, 2000);
            }
          }, i * 100);
        });
      }, existingSteps.length * 50);

      /* Enhanced animated fill with smooth progress */
      if (fEl) {
        var currentWidth = fEl.style.width || '0px';
        var pct = (STAGES.length <= 1) ? 0 : (Math.max(1, o.currentStageIndex || 1) / (STAGES.length - 1)) * 100;
        var fillWidth = pct <= 0 ? '0px' : 'calc(' + pct + '% - 0px)';
        
        // Smooth transition to new width
        setTimeout(function() {
          fEl.style.transition = 'width 0.8s cubic-bezier(0.4, 0, 0.2, 1)';
          fEl.style.width = fillWidth;
          
          // Add glow effect for active progress
          if (pct > 0 && pct < 100) {
            fEl.style.boxShadow = '0 0 20px rgba(199, 159, 94, 0.6)';
            setTimeout(function() {
              fEl.style.boxShadow = 'none';
            }, 800);
          }
        }, 100);
      }
    }

    var curStage = STAGES[Math.max(0, o.currentStageIndex || 1)] || STAGES[0];
    var ic = g('cfStIcon'); var ti = g('cfStTitle'); var su = g('cfStSub');
    
    /* Enhanced status card with animated icon transitions */
    if (ic) {
      // Fade out current icon
      ic.style.transition = 'all 0.3s ease';
      ic.style.opacity = '0';
      ic.style.transform = 'scale(0.8) rotate(-10deg)';
      
      setTimeout(function() {
        // Update icon with rotation effect
        ic.innerHTML = STAGE_SVGS[Math.min(Math.max(0, o.currentStageIndex || 1), STAGE_SVGS.length - 1)];
        
        // Delivered state — green with celebration
        if (o.status === 'delivered') {
          ic.style.background = '#26de81';
          ic.style.borderColor = '#26de81';
          ic.style.transform = 'scale(1.1) rotate(5deg)';
          ic.querySelector('svg') && (ic.querySelector('svg').style.stroke = '#fff');
        } else if (ic) {
          ic.style.background = '#f0e6cc';
          ic.style.borderColor = '#e8d5a3';
          ic.style.transform = 'scale(1) rotate(0deg)';
          ic.querySelector('svg') && (ic.querySelector('svg').style.stroke = '#c9a84c');
        }
        
        // Fade in new icon
        setTimeout(function() {
          ic.style.opacity = '1';
          ic.style.transform = 'scale(1) rotate(0deg)';
        }, 50);
      }, 300);
    }
    
    /* Update status text with slide animation */
    if (ti) {
      ti.style.transition = 'all 0.3s ease';
      ti.style.opacity = '0';
      ti.style.transform = 'translateX(-10px)';
      
      setTimeout(function() {
        ti.textContent = curStage.label;
        ti.style.opacity = '1';
        ti.style.transform = 'translateX(0)';
      }, 150);
    }
    
    if (su) {
      su.style.transition = 'all 0.3s ease';
      su.style.opacity = '0';
      su.style.transform = 'translateX(-10px)';
      
      setTimeout(function() {
        su.textContent = curStage.sub;
        su.style.opacity = '1';
        su.style.transform = 'translateX(0)';
      }, 200);
    }

    /* Add floating animation for active orders */
    var progCard = g('cfProgCard');
    if (progCard && o.status !== 'delivered') {
      progCard.classList.add('floating');
    } else if (progCard) {
      progCard.classList.remove('floating');
    }

    /* ── Items ordered card ── */
    var tic = g('cfTrkItemsCard');
    if (tic && o.items && o.items.length) {
      var total = o.totalAmount || o.items.reduce(function(s,i){ return s + (i.price||0)*(i.qty||1); }, 0);
      var html = '<div class="cfTITitle">Items Ordered</div>';
      o.items.forEach(function(i) {
        html += '<div class="cfTIRow"><span>' + i.name + ' x' + (i.qty||1) + '</span><span>&#8369;' + ((i.price||0)*(i.qty||1)).toFixed(2) + '</span></div>';
      });
      html += '<div class="cfTIRow cfTITot"><span><strong>Total</strong></span><span><strong>&#8369;' + (total).toFixed(2) + '</strong></span></div>';
      tic.innerHTML = html;
      tic.style.display = 'block';
    }

    /* ── Seat strip ── */
    var seatEl = g('cfTrkSeat');
    if (seatEl && o.seatNumber) {
      seatEl.innerHTML = '🏃 On the way to <strong>Seat ' + o.seatNumber + '</strong>';
      seatEl.style.display = 'flex';
    }
  }

  /* Celebration animation for delivered orders */
  function celebrateDelivery() {
    var panel = g('cfTrkPanel');
    if (!panel) return;
    
    // Create confetti effect
    for (var i = 0; i < 20; i++) {
      setTimeout(function() {
        var confetti = document.createElement('div');
        confetti.style.cssText = `
          position: absolute;
          width: 8px;
          height: 8px;
          background: ${['#c79f5e', '#26de81', '#2196f3', '#ff9800'][Math.floor(Math.random() * 4)]};
          border-radius: 50%;
          top: 50%;
          left: 50%;
          pointer-events: none;
          z-index: 10000;
        `;
        panel.appendChild(confetti);
        
        // Animate confetti
        var angle = (Math.PI * 2 * i) / 20;
        var velocity = 200 + Math.random() * 100;
        var lifetime = 1000 + Math.random() * 1000;
        
        confetti.animate([
          { 
            transform: 'translate(-50%, -50%) scale(1)',
            opacity: 1 
          },
          { 
            transform: `translate(calc(-50% + ${Math.cos(angle) * velocity}px), calc(-50% + ${Math.sin(angle) * velocity}px)) scale(0)`,
            opacity: 0 
          }
        ], {
          duration: lifetime,
          easing: 'cubic-bezier(0.25, 0.46, 0.45, 0.94)'
        }).onfinish = function() { confetti.remove(); };
      }, i * 50);
    }
    
    // Add success pulse to panel
    panel.style.transition = 'all 0.3s ease';
    panel.style.transform = 'scale(1.02)';
    setTimeout(function() {
      panel.style.transform = 'scale(1)';
    }, 300);
  }

  /* ── RECEIPT ──────────────────────────────────────────────────────────── */
  async function dlReceipt(orderId, items, subtotal, finalTotal) {
    var d = { orderId: orderId, items: items || [], subtotal: subtotal || 0, finalTotal: finalTotal || 0 };
    if (!items || !items.length) {
      try {
        var r = await fetch(RECEIPT_API + '?orderId=' + encodeURIComponent(orderId));
        var j = await r.json();
        if (j.success && j.order) d = Object.assign({}, j.order);
      } catch (e) { console.error(e); }
    }
    var s    = d.subtotal || 0;
    var disc = (d.discountAmount != null) ? d.discountAmount : 0;
    var fin  = (d.finalTotal    != null) ? d.finalTotal    : s;
    var html = '<!DOCTYPE html><html><head><meta charset="utf-8"><title>CineFlix Receipt</title>' +
      '<style>*{margin:0;padding:0;box-sizing:border-box}body{font-family:Segoe UI,sans-serif;background:#0f0f12;color:#eaeaea;display:flex;align-items:center;justify-content:center;min-height:100vh;padding:24px}' +
      '.rc{max-width:420px;width:100%;background:linear-gradient(180deg,#1a1a2e,#16213e);border:1px solid rgba(199,159,94,.4);border-radius:16px;overflow:hidden}' +
      '.rh{background:linear-gradient(135deg,#c79f5e,#d98639);padding:24px;text-align:center}.rh h1{font-size:1.6rem;color:#fff;letter-spacing:2px}.rh p{font-size:.85rem;color:rgba(255,255,255,.9);margin-top:4px}' +
      '.rb{padding:24px}.hr{border:none;border-top:1px dashed rgba(255,255,255,.15);margin:16px 0}' +
      '.rl{display:flex;justify-content:space-between;padding:6px 0;font-size:.9rem;border-bottom:1px solid rgba(255,255,255,.06)}.rl:last-child{border-bottom:none}' +
      '.rs{font-weight:700;color:#c79f5e;margin:16px 0 8px;font-size:.8rem;text-transform:uppercase}' +
      '.rt{font-weight:700;font-size:1.1rem;color:#c79f5e;margin-top:12px;padding-top:12px;border-top:2px solid rgba(199,159,94,.4)}' +
      '.rf{padding:16px;text-align:center;font-size:.8rem;color:rgba(255,255,255,.5);background:rgba(0,0,0,.2)}</style></head><body>' +
      '<div class="rc"><div class="rh"><h1>CineFlix</h1><p>Food and Beverage Receipt</p></div><div class="rb">' +
      '<div class="rl"><span>Order #</span><span>' + (d.orderId || orderId) + '</span></div>' +
      '<div class="rl"><span>Date</span><span>' + (d.createdAt ? new Date(d.createdAt).toLocaleString() : new Date().toLocaleString()) + '</span></div>' +
      (d.seatNumber ? '<div class="rl"><span>Seat</span><span>' + d.seatNumber + '</span></div>' : '') +
      '<hr class="hr"><div class="rs">Items</div>' +
      (d.items || []).map(function (i) { return '<div class="rl"><span>' + i.name + ' x' + i.qty + '</span><span>P' + (i.price * i.qty).toFixed(2) + '</span></div>'; }).join('') +
      '<hr class="hr"><div class="rl"><span>Subtotal</span><span>P' + s.toFixed(2) + '</span></div>' +
      '<div class="rl"><span>Discount (20%)</span><span style="color:#26de81">-P' + disc.toFixed(2) + '</span></div>' +
      '<div class="rl rt"><span>Total Paid</span><span>P' + fin.toFixed(2) + '</span></div>' +
      '</div><div class="rf">Thank you for choosing CineFlix!</div></div></body></html>';
    var blob = new Blob([html], { type: 'text/html' });
    var a    = document.createElement('a');
    a.href   = URL.createObjectURL(blob);
    a.download = 'CineFlix-Receipt-' + (d.orderId || orderId) + '.html';
    a.click();
    URL.revokeObjectURL(a.href);
  }

  window.CineFlixChatbot = { openCart: openCart, openTrack: openTrack, doInput: doInput };
})();