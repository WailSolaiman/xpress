;(function ($) {
  "use strict"

  function addNotice(type, text) {
    const area = $("#xppwots-notices")
    const $n = $(
      `<div class="notice notice-${type} is-dismissible"><p>${text}</p></div>`
    )
    area.append($n)
    setTimeout(() => {
      $n.fadeOut(200, () => {
        $n.remove()
      })
    }, 3500)
  }

  async function restPost(path, payload) {
    const url = `${XPPWOTS_DATA.restUrl}${path}`
    const res = await fetch(url, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "X-WP-Nonce": XPPWOTS_DATA.nonce,
      },
      body: JSON.stringify(payload),
    })
    if (!res.ok) throw new Error("HTTP " + res.status)
    return res.json()
  }

  const t = XPPWOTS_DATA.i18n
  const RAW_LOCALE =
    (XPPWOTS_DATA && XPPWOTS_DATA.ui && XPPWOTS_DATA.ui.locale) ||
    (document.documentElement && document.documentElement.lang) ||
    navigator.language ||
    "en"
  const FORMAT_LOCALE = String(RAW_LOCALE).replace(/_/g, "-") || "en"

  let XPPWOTS_LAST = { handle: "", profile: null, tweets: [] }

  function setState(id) {
    $("#xppwots-loading").prop("hidden", id !== "loading")
    $("#xppwots-empty").prop("hidden", id !== "empty")
    $("#xppwots-error").prop("hidden", id !== "error")
    $("#xppwots-list").toggle(id === "list")
  }

  function autolink(text) {
    const esc = (s) =>
      s.replace(
        /[&<>"]/g,
        (m) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;" }[m])
      )
    let out = esc(text || "")
    out = out.replace(
      /(https?:\/\/[^\s]+)/g,
      '<a href="$1" target="_blank" rel="noopener noreferrer">$1</a>'
    )
    return out.replace(/\n/g, "<br>")
  }

  function fmtCount(n) {
    if (n === undefined || n === null) return "0"
    const v = Number(n)
    if (!isFinite(v)) return String(n)
    if (v >= 1e9) return (v / 1e9).toFixed(1).replace(/\.0$/, "") + "B"
    if (v >= 1e6) return (v / 1e6).toFixed(1).replace(/\.0$/, "") + "M"
    if (v >= 1e3) return (v / 1e3).toFixed(1).replace(/\.0$/, "") + "K"
    return String(v)
  }

  function parseDateAny(s) {
    if (!s) return null
    let str = String(s)
      .replace(/\u00A0/g, " ")
      .replace(/\s+/g, " ")
      .trim()

    let d = new Date(str)
    if (!isNaN(d)) return d

    str = str
      .replace(/\s+UTC$/i, "")
      .replace(/([+-]\d{2})(\d{2})\b/, (_, h, m) =>
        h === "+00" && m === "00" ? "Z" : `${h}:${m}`
      )

    d = new Date(str)
    if (!isNaN(d)) return d

    const m = str.match(
      /^([A-Za-z]{3}),?\s+([A-Za-z]{3})\s+(\d{1,2}),?\s+(\d{2}):(\d{2}):(\d{2})\s+([+-]\d{4}|Z)\s+(\d{4})$/
    )
    if (m) {
      const monthMap = {
        Jan: 0,
        Feb: 1,
        Mar: 2,
        Apr: 3,
        May: 4,
        Jun: 5,
        Jul: 6,
        Aug: 7,
        Sep: 8,
        Oct: 9,
        Nov: 10,
        Dec: 11,
      }
      const mon = monthMap[m[2]]
      if (mon !== undefined) {
        const yyyy = parseInt(m[8], 10)
        const dd = parseInt(m[3], 10)
        const hh = parseInt(m[4], 10)
        const mi = parseInt(m[5], 10)
        const ss = parseInt(m[6], 10)
        if (m[7] === "Z") {
          return new Date(Date.UTC(yyyy, mon, dd, hh, mi, ss))
        } else {
          const sign = m[7][0] === "+" ? 1 : -1
          const oh = parseInt(m[7].slice(1, 3), 10)
          const om = parseInt(m[7].slice(3, 5), 10)
          const offsetMin = sign * (oh * 60 + om)
          const utcMs =
            Date.UTC(yyyy, mon, dd, hh, mi, ss) - offsetMin * 60 * 1000
          return new Date(utcMs)
        }
      }
    }

    if (/^\d+$/.test(str)) {
      const num = Number(str)
      const epochMs = str.length <= 10 ? num * 1000 : num
      d = new Date(epochMs)
      if (!isNaN(d)) return d
    }
    return null
  }

  function fmtDate(s) {
    try {
      const d = parseDateAny(s)
      if (!d) return s || ""
      return new Intl.DateTimeFormat(FORMAT_LOCALE, {
        dateStyle: "medium",
        timeStyle: "short",
      }).format(d)
    } catch {
      return s || ""
    }
  }

  function renderProfile(p) {
    if (!p) {
      $("#xppwots-profile").prop("hidden", true)
      return
    }
    $("#xppwots-avatar")
      .attr("src", p.image_url || "")
      .attr("alt", p.name || "")
    $("#xppwots-name").text(p.name || "")
    $("#xppwots-screen").text(p.screen_name ? "@" + p.screen_name : "")
    $("#xppwots-bio").text(p.description || "")
    $("#xppwots-profile").prop("hidden", false)
  }

  function renderTweets(list) {
    const $grid = $("#xppwots-list").empty()
    list.forEach((tw) => {
      const hasBookmarks =
        typeof tw.bookmark_count !== "undefined" && tw.bookmark_count !== null
      const $card = $(`
        <article class="xppwots-tweet" tabindex="0">
          <div class="xppwots-tweet-text">${autolink(tw.full_text || "")}</div>
          <div class="xppwots-metrics">
            <div class="left">
              <div class="m" title="${t.tw_likes}">❤️ <span>${fmtCount(
        tw.favorite_count
      )}</span></div>
              <div class="m" title="${t.tw_retweets}">🔁 <span>${fmtCount(
        tw.retweet_count
      )}</span></div>
              <div class="m" title="${t.tw_replies}">💬 <span>${fmtCount(
        tw.reply_count
      )}</span></div>
              <div class="m" title="${t.tw_views}">👁️ <span>${fmtCount(
        tw.views
      )}</span></div>
              ${
                hasBookmarks
                  ? `<div class="m" title="${
                      t.tw_bookmarks
                    }">🔖 <span>${fmtCount(tw.bookmark_count)}</span></div>`
                  : ``
              }
            </div>
            <div class="xppwots-date">${fmtDate(tw.created_at)}</div>
          </div>
          <div class="xppwots-actions">
            <a class="button button-secondary" target="_blank" rel="noopener noreferrer" href="${
              tw.url || "#"
            }">${t.tw_open || "Open"}</a>
          </div>
        </article>
      `)
      $grid.append($card)
    })
  }

  async function parseRes(res) {
    const txt = await res.text()
    try {
      return JSON.parse(txt)
    } catch {
      return txt
    }
  }

  function firstProfile(json) {
    if (Array.isArray(json) && json[0]) return json[0]
    if (typeof json === "string") {
      try {
        const j = JSON.parse(json)
        if (Array.isArray(j) && j[0]) return j[0]
      } catch {}
    }
    if (json && typeof json === "object") return json
    return null
  }

  function pickTweets(json) {
    if (Array.isArray(json) && json[0] && Array.isArray(json[0].tweets))
      return json[0].tweets
    if (json && Array.isArray(json.tweets)) return json.tweets
    if (
      Array.isArray(json) &&
      json.length &&
      typeof json[0] === "object" &&
      "full_text" in json[0]
    )
      return json
    if (typeof json === "string") {
      try {
        const j = JSON.parse(json)
        return pickTweets(j)
      } catch {}
    }
    return []
  }

  function tweetIdFromUrl(u) {
    try {
      const m = String(u || "").match(/status\/(\d+)/)
      return m ? m[1] : ""
    } catch {
      return ""
    }
  }

  async function fetchTweetsFlow(handle) {
    setState("loading")
    $("#xppwots-profile").prop("hidden", true)
    $("#xppwots-save-results").prop("disabled", true)
    const epProfile =
      XPPWOTS_DATA.endpoints && XPPWOTS_DATA.endpoints.profile
        ? XPPWOTS_DATA.endpoints.profile
        : ""
    const epTweets =
      XPPWOTS_DATA.endpoints && XPPWOTS_DATA.endpoints.tweets
        ? XPPWOTS_DATA.endpoints.tweets
        : ""
    try {
      if (!epProfile || !epTweets) throw new Error("missing endpoints")
      const pJson = await parseRes(
        await fetch(`${epProfile}?handle=${encodeURIComponent(handle)}`)
      )
      const profile = firstProfile(pJson)
      renderProfile(profile)
      const tJson = await parseRes(
        await fetch(`${epTweets}?handle=${encodeURIComponent(handle)}`)
      )
      const tweets = pickTweets(tJson)
      if (!tweets.length) {
        XPPWOTS_LAST = { handle, profile, tweets: [] }
        setState("empty")
        return
      }
      renderTweets(tweets)
      XPPWOTS_LAST = { handle, profile, tweets }
      $("#xppwots-save-results").prop("disabled", false)
      setState("list")
    } catch {
      setState("error")
    }
  }

  $(document).on("click", "#xppwots-fetch", function () {
    const h = String($("#xppwots-handle").val() || "")
      .trim()
      .replace(/^@+/, "")
    if (!h) {
      addNotice("warning", t.msg_generic_error)
      return
    }
    fetchTweetsFlow(h)
  })

  $(document).on("click", "#xppwots-retry", function () {
    const h = String($("#xppwots-handle").val() || "")
      .trim()
      .replace(/^@+/, "")
    if (!h) {
      addNotice("warning", t.msg_generic_error)
      return
    }
    fetchTweetsFlow(h)
  })

  $(document).on("click", "#xppwots-save-results", async function () {
    if (!XPPWOTS_LAST.tweets || !XPPWOTS_LAST.tweets.length) {
      addNotice("warning", t.tw_empty || "No tweets")
      return
    }
    const handle = XPPWOTS_LAST.handle || ""
    const payload = {
      handle,
      profile: {
        name:
          XPPWOTS_LAST.profile && XPPWOTS_LAST.profile.name
            ? XPPWOTS_LAST.profile.name
            : "",
        screen_name:
          XPPWOTS_LAST.profile && XPPWOTS_LAST.profile.screen_name
            ? XPPWOTS_LAST.profile.screen_name
            : "",
        image_url:
          XPPWOTS_LAST.profile && XPPWOTS_LAST.profile.image_url
            ? XPPWOTS_LAST.profile.image_url
            : "",
        description:
          XPPWOTS_LAST.profile && XPPWOTS_LAST.profile.description
            ? XPPWOTS_LAST.profile.description
            : "",
      },
      tweets: XPPWOTS_LAST.tweets.map((tw) => ({
        tweet_id: tweetIdFromUrl(tw.url),
        url: tw.url || "",
        full_text: tw.full_text || "",
        created_at: tw.created_at || "",
        favorite_count: Number(tw.favorite_count || 0),
        retweet_count: Number(tw.retweet_count || 0),
        reply_count: Number(tw.reply_count || 0),
        views: Number(tw.views || 0),
        bookmark_count: Number(tw.bookmark_count || 0),
      })),
    }
    try {
      const res = await restPost("/import", payload)
      if (res && res.ok) {
        addNotice(
          "success",
          `تم: إنشاء ${res.created || 0} | تحديث ${res.updated || 0} | تخطّي ${
            res.skipped || 0
          }`
        )
      } else {
        addNotice("error", t.msg_generic_error)
      }
    } catch {
      addNotice("error", t.msg_network_error)
    }
  })

  async function restGet(path) {
    const url = `${XPPWOTS_DATA.restUrl}${path}`
    const res = await fetch(url, {
      headers: { "X-WP-Nonce": XPPWOTS_DATA.nonce },
    })
    if (!res.ok) throw new Error(res.status)
    return res.json()
  }

  function r_setState(id) {
    $("#xppwots-rec-loading").prop("hidden", id !== "loading")
    $("#xppwots-rec-empty").prop("hidden", id !== "empty")
    $("#xppwots-rec-error").prop("hidden", id !== "error")
    $("#xppwots-groups-wrap").prop("hidden", id !== "list")
  }

  function r_fmtDate(s) {
    try {
      const d = parseDateAny(s)
      if (!d) return s || ""
      return new Intl.DateTimeFormat(FORMAT_LOCALE, {
        dateStyle: "medium",
        timeStyle: "short",
      }).format(d)
    } catch {
      return s || ""
    }
  }

  function r_fmtNum(n) {
    const v = Number(n || 0)
    if (v >= 1e9) return (v / 1e9).toFixed(1).replace(/\.0$/, "") + "B"
    if (v >= 1e6) return (v / 1e6).toFixed(1).replace(/\.0$/, "") + "M"
    if (v >= 1e3) return (v / 1e3).toFixed(1).replace(/\.0$/, "") + "K"
    return String(v)
  }

  function r_autolink(t) {
    const esc = (s) =>
      String(s || "").replace(
        /[&<>"]/g,
        (m) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;" }[m])
      )
    return esc(t)
      .replace(
        /(https?:\/\/[^\s]+)/g,
        '<a href="$1" target="_blank" rel="noopener noreferrer">$1</a>'
      )
      .replace(/\n/g, "<br>")
  }

  function r_render(groups) {
    const $tb = $("#xppwots-groups-body").empty()
    if (!groups.length) {
      r_setState("empty")
      return
    }
    groups.forEach((g) => {
      const img =
        g.profile && g.profile.image_url
          ? `<img src="${g.profile.image_url}" alt="" style="width:28px;height:28px;border-radius:50%;object-fit:cover;margin-inline-start:6px;">`
          : ""
      const displayName =
        g.profile && g.profile.name ? g.profile.name : "@" + g.handle
      const totals = `❤ ${r_fmtNum(g.totals.likes)} • 🔁 ${r_fmtNum(
        g.totals.retweets
      )} • 💬 ${r_fmtNum(g.totals.replies)} • 👁️ ${r_fmtNum(g.totals.views)}`
      const $tr = $(`
        <tr class="xppwots-group-row" data-handle="${g.handle}">
          <td data-label="${XPPWOTS_DATA.i18n.col_name}"></td>
          <td data-label="${XPPWOTS_DATA.i18n.col_tweets}"></td>
          <td data-label="${XPPWOTS_DATA.i18n.col_latest}"></td>
          <td data-label="${XPPWOTS_DATA.i18n.col_totals}"></td>
          <td data-label="${XPPWOTS_DATA.i18n.actions}"></td>
        </tr>
      `)
      const lp = XPPWOTS_DATA.i18n.last_posted || "Last posted"
      const ls = XPPWOTS_DATA.i18n.last_saved || "Last saved"
      $tr.find("td").eq(0).html(`
        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
          ${img}
          <div>
            <strong>${displayName}</strong>
            <div class="description">@${g.handle}</div>
            <div class="description">${lp}: ${
        r_fmtDate(g.latest_created_at) || "—"
      } | ${ls}: ${r_fmtDate(g.latest_fetched_at) || "—"}</div>
          </div>
        </div>
      `)
      $tr.find("td").eq(1).text(g.count)
      $tr
        .find("td")
        .eq(2)
        .text(r_fmtDate(g.latest_created_at) || "—")
      $tr.find("td").eq(3).text(totals)
      $tr.find("td").eq(4).html(`
        <button class="button xppwots-row-toggle" aria-expanded="false">${
          XPPWOTS_DATA.i18n.show || "Show"
        }</button>
        <button class="button button-link-delete xppwots-row-delete">${
          XPPWOTS_DATA.i18n.delete || "Delete"
        }</button>
      `)

      const $details = $(
        `<tr class="xppwots-group-details" data-handle="${g.handle}" hidden><td colspan="5"><div class="xppwots-group-list"></div></td></tr>`
      )
      const $list = $details.find(".xppwots-group-list")
      $list.css({
        display: "grid",
        gap: "10px",
        gridTemplateColumns: "repeat(auto-fill, minmax(260px, 1fr))",
      })
      g.tweets.forEach((tw) => {
        const $item = $(`
          <article class="xppwots-tweet" style="margin:0;padding:10px;border:1px solid #ddd;border-radius:8px;background:#fff;">
            <div class="xppwots-tweet-text" style="margin-bottom:8px;">${r_autolink(
              tw.full_text || ""
            )}</div>
            <div class="xppwots-metrics" style="display:flex;justify-content:space-between;gap:8px;align-items:center;">
              <div class="left" style="display:flex;gap:10px;flex-wrap:wrap;">
                <div class="m">❤ <span>${r_fmtNum(
                  tw.favorite_count
                )}</span></div>
                <div class="m">🔁 <span>${r_fmtNum(
                  tw.retweet_count
                )}</span></div>
                <div class="m">💬 <span>${r_fmtNum(tw.reply_count)}</span></div>
                <div class="m">👁️ <span>${r_fmtNum(tw.views)}</span></div>
                ${
                  typeof tw.bookmark_count !== "undefined"
                    ? `<div class="m">🔖 <span>${r_fmtNum(
                        tw.bookmark_count
                      )}</span></div>`
                    : ``
                }
              </div>
              <div class="xppwots-date">${r_fmtDate(tw.created_at)}</div>
            </div>
            <div class="xppwots-actions" style="margin-top:8px;">
              <a class="button button-secondary" target="_blank" rel="noopener noreferrer" href="${
                tw.url || "#"
              }">${XPPWOTS_DATA.i18n.tw_open || "Open"}</a>
            </div>
          </article>
        `)
        $list.append($item)
      })
      $("#xppwots-groups-body").append($tr, $details)
    })
    r_setState("list")
  }

  async function r_load() {
    r_setState("loading")
    try {
      const groups = await restGet("/groups")
      r_render(Array.isArray(groups) ? groups : [])
    } catch {
      r_setState("error")
    }
  }

  $(document).on(
    "click",
    "#xppwots-groups-refresh,#xppwots-groups-retry",
    r_load
  )

  $(document).on("click", ".xppwots-row-toggle", function () {
    const $btn = $(this)
    const handle = $btn.closest("tr").data("handle")
    const $det = $(`.xppwots-group-details[data-handle="${handle}"]`)
    const isOpen = !$det.prop("hidden")
    $det.prop("hidden", isOpen)
    $btn
      .attr("aria-expanded", String(!isOpen))
      .text(
        isOpen
          ? XPPWOTS_DATA.i18n.show || "Show"
          : XPPWOTS_DATA.i18n.hide || "Hide"
      )
  })

  $(document).on("click", ".xppwots-row-delete", async function () {
    const $row = $(this).closest("tr")
    const handle = $row.data("handle")
    const msg =
      XPPWOTS_DATA.i18n && XPPWOTS_DATA.i18n.confirm_delete
        ? XPPWOTS_DATA.i18n.confirm_delete.replace("%s", "@" + handle)
        : "Delete all tweets for @" + handle + "? This cannot be undone."
    if (!window.confirm(msg)) return
    try {
      const res = await $.ajax({
        url: `${XPPWOTS_DATA.restUrl}/purge`,
        method: "POST",
        headers: {
          "X-WP-Nonce": XPPWOTS_DATA.nonce,
          "Content-Type": "application/json",
        },
        data: JSON.stringify({ handle }),
      })
      if (res && res.ok) {
        const $det = $(`.xppwots-group-details[data-handle="${handle}"]`)
        $det.remove()
        $row.remove()
        addNotice("success", XPPWOTS_DATA.i18n.deleted || "Deleted")
        if (!$("#xppwots-groups-body").children().length) r_setState("empty")
      } else {
        addNotice("error", XPPWOTS_DATA.i18n.msg_generic_error || "Failed")
      }
    } catch {
      addNotice("error", XPPWOTS_DATA.i18n.msg_network_error || "Network error")
    }
  })

  if (new URLSearchParams(location.search).get("page") === "xppwots-records") {
    r_load()
  }
})(jQuery)
