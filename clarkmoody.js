// clarkmoody.js
// Node 18+ (has global fetch). For Node <18, install node-fetch.

const DASH_URL = "https://dashboard.clarkmoody.com/";

/**
 * Returns the current Hash Price (PHash/s) in USD, e.g. 37.31
 */
export async function fetchDailyPHashUsd() {
  const res = await fetch(DASH_URL, {
    headers: {
      "User-Agent": "watts-worth-it/1.0",
      "Accept": "text/plain,*/*",
    },
  });

  if (!res.ok) {
    throw new Error(`Clark Moody fetch failed: HTTP ${res.status}`);
  }

  const text = await res.text();

  // Looks like:
  // "Hash Price (PHash/s)\n\n$37.31"
  const match = text.match(/Hash Price\s*\(PHash\/s\)[\s\S]*?\$([0-9]+(?:\.[0-9]+)?)/i);

  if (!match) {
    throw new Error("Could not find 'Hash Price (PHash/s)' in dashboard text");
  }

  return Number(match[1]);
}

/**
 * Converts $/PH/day to $/TH/day (1 PH = 1000 TH)
 */
export function phToThPerDay(phUsdPerDay) {
  return phUsdPerDay / 1000;
}

// If run directly: print JSON you can save/use
if (import.meta.url === `file://${process.argv[1]}`) {
  (async () => {
    const ph = await fetchDailyPHashUsd();
    const th = phToThPerDay(ph);
    const payload = {
      source: DASH_URL,
      dailyPHashUsd: Number(ph.toFixed(2)),
      dailyThUsd: Number(th.toFixed(5)),
      ts: new Date().toISOString(),
    };
    console.log(JSON.stringify(payload, null, 2));
  })().catch((err) => {
    console.error(err);
    process.exit(1);
  });
}
