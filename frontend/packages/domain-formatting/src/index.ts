export type DecimalString = string;
export type MoneyAmount = DecimalString;
export type QuantityAmount = DecimalString;
export type BusinessDate = `${number}-${number}-${number}`;

type DecimalParts = {
  fraction: string;
  integer: string;
  negative: boolean;
};

const decimalExpression = /^-?\d+(?:\.\d+)?$/;
const businessDateExpression = /^(\d{4,})-(\d{2})-(\d{2})$/;

function parseDecimal(value: DecimalString): DecimalParts {
  if (!decimalExpression.test(value)) {
    throw new TypeError("Decimal values must use a plain decimal string.");
  }

  const negative = value.startsWith("-");
  const unsignedValue = negative ? value.slice(1) : value;
  const separatorIndex = unsignedValue.indexOf(".");
  const rawInteger = separatorIndex === -1 ? unsignedValue : unsignedValue.slice(0, separatorIndex);
  const fraction = separatorIndex === -1 ? "" : unsignedValue.slice(separatorIndex + 1);
  const integer = rawInteger.replace(/^0+(?=\d)/, "");

  return {
    fraction,
    integer,
    negative: negative && /[1-9]/.test(`${integer}${fraction}`),
  };
}

function formatDecimal(value: DecimalString, locale: string, trimFraction = false): string {
  const { fraction: rawFraction, integer, negative } = parseDecimal(value);
  const group =
    new Intl.NumberFormat(locale).formatToParts(1000).find((part) => part.type === "group")
      ?.value ?? ",";
  const decimal =
    new Intl.NumberFormat(locale).formatToParts(1.1).find((part) => part.type === "decimal")
      ?.value ?? ".";
  const digitFormatter = new Intl.NumberFormat(locale, { useGrouping: false });
  const localizedDigits = Array.from({ length: 10 }, (_, digit) => digitFormatter.format(digit));
  const groups: string[] = [];

  for (let cursor = integer.length; cursor > 0; cursor -= 3) {
    groups.unshift(integer.slice(Math.max(0, cursor - 3), cursor));
  }

  const localizeDigits = (digits: string) =>
    digits.replace(/\d/g, (digit) => localizedDigits[Number(digit)] ?? digit);
  const fraction = trimFraction ? rawFraction.replace(/0+$/, "") : rawFraction;
  const sign = negative ? "-" : "";
  const formattedInteger = localizeDigits(groups.join(group));

  return `${sign}${formattedInteger}${fraction.length > 0 ? `${decimal}${localizeDigits(fraction)}` : ""}`;
}

function shiftDecimalForPercentage(value: DecimalString): DecimalString {
  const { fraction, integer, negative } = parseDecimal(value);
  const shiftedInteger =
    `${integer}${fraction.padEnd(2, "0").slice(0, 2)}`.replace(/^0+(?=\d)/, "") || "0";
  const shiftedFraction = fraction.slice(2).replace(/0+$/, "");

  return `${negative ? "-" : ""}${shiftedInteger}${shiftedFraction.length > 0 ? `.${shiftedFraction}` : ""}`;
}

function formatWithCurrencyTemplate(
  value: DecimalString,
  currency: string,
  locale: string,
): string {
  const { negative } = parseDecimal(value);
  const formatter = new Intl.NumberFormat(locale, { currency, style: "currency" });
  const template = formatter.formatToParts(negative ? -1 : 1);
  const formattedDecimal = formatDecimal(value, locale);
  let insertedDecimal = false;

  return template
    .flatMap((part) => {
      if (["integer", "group", "decimal", "fraction"].includes(part.type)) {
        if (insertedDecimal) {
          return [];
        }

        insertedDecimal = true;

        return [formattedDecimal.replace(/^-/, "")];
      }

      return [part.value];
    })
    .join("");
}

function formatWithPercentTemplate(value: DecimalString, locale: string): string {
  const { negative } = parseDecimal(value);
  const formatter = new Intl.NumberFormat(locale, { style: "percent" });
  const template = formatter.formatToParts(negative ? -1 : 1);
  const formattedDecimal = formatDecimal(value, locale, true);
  let insertedDecimal = false;

  return template
    .flatMap((part) => {
      if (["integer", "group", "decimal", "fraction"].includes(part.type)) {
        if (insertedDecimal) {
          return [];
        }

        insertedDecimal = true;

        return [formattedDecimal.replace(/^-/, "")];
      }

      return [part.value];
    })
    .join("");
}

export function formatMoney(
  amount: MoneyAmount,
  { currency, locale = "en-US" }: { currency: string; locale?: string },
): string {
  return formatWithCurrencyTemplate(amount, currency, locale);
}

export function formatQuantity(
  quantity: QuantityAmount,
  { locale = "en-US", unit }: { locale?: string; unit?: string } = {},
): string {
  const formattedQuantity = formatDecimal(quantity, locale);

  return unit === undefined ? formattedQuantity : `${formattedQuantity} ${unit}`;
}

export function formatBusinessDate(
  businessDate: BusinessDate,
  {
    dateStyle = "medium",
    locale = "en-US",
  }: { dateStyle?: Intl.DateTimeFormatOptions["dateStyle"]; locale?: string } = {},
): string {
  const match = businessDateExpression.exec(businessDate);

  if (match === null) {
    throw new TypeError("Business dates must use YYYY-MM-DD.");
  }

  const [, year, month, day] = match;
  const date = new Date(Date.UTC(Number(year), Number(month) - 1, Number(day)));

  if (
    date.getUTCFullYear() !== Number(year) ||
    date.getUTCMonth() !== Number(month) - 1 ||
    date.getUTCDate() !== Number(day)
  ) {
    throw new TypeError("Business dates must be valid calendar dates.");
  }

  return new Intl.DateTimeFormat(locale, { dateStyle, timeZone: "UTC" }).format(date);
}

export function formatDateTime(
  timestamp: string,
  {
    dateStyle = "medium",
    locale = "en-US",
    timeStyle = "short",
    timeZone,
  }: {
    dateStyle?: Intl.DateTimeFormatOptions["dateStyle"];
    locale?: string;
    timeStyle?: Intl.DateTimeFormatOptions["timeStyle"];
    timeZone: string;
  },
): string {
  const date = new Date(timestamp);

  if (Number.isNaN(date.getTime())) {
    throw new TypeError("Date-time values must be valid ISO timestamps.");
  }

  return new Intl.DateTimeFormat(locale, { dateStyle, timeStyle, timeZone }).format(date);
}

export function formatPercentage(
  value: DecimalString,
  {
    locale = "en-US",
    valueKind = "ratio",
  }: { locale?: string; valueKind?: "ratio" | "percentage" } = {},
): string {
  const percentage = valueKind === "ratio" ? shiftDecimalForPercentage(value) : value;

  return formatWithPercentTemplate(percentage, locale);
}
