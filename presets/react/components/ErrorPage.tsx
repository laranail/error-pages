/**
 * The React error-page component — a faithful port of the shared DOM contract
 * (.ep-shell / .ep-card / .ep-brand / .ep-status / .ep-title / .ep-message /
 * .ep-actions), driven by the one ErrorPagePayload. Styled by the shared CSS
 * (`@laranail/error-pages-ui/style.css`); the final design lands with the
 * canonical templates. Used by the Inertia and React-SPA stacks.
 */
import type { ErrorPagePayload } from '../../shared/ts/payload';
import { themeClass } from '../../shared/ts/payload';

export function ErrorPage({ page }: { page: ErrorPagePayload }) {
  return (
    <div className={themeClass(page)}>
      <main className="ep-shell" role="main">
        <section className="ep-card">
          <div className="ep-brand">
            {page.brand.logo ? (
              <img className="ep-logo" src={page.brand.logo} alt={page.brand.name} />
            ) : (
              <span className="ep-brand-name">{page.brand.name}</span>
            )}
          </div>
          <p className="ep-status" aria-hidden="true">
            {page.code}
          </p>
          <h1 className="ep-title">{page.title}</h1>
          <p className="ep-message">{page.message}</p>
          <div className="ep-actions">
            <a className="ep-btn ep-btn-primary" href={page.homeUrl}>
              Back to home
            </a>
          </div>
          {page.requestId ? (
            <p className="ep-ref">
              Reference: <code>{page.requestId}</code>
            </p>
          ) : null}
        </section>
      </main>
    </div>
  );
}

export default ErrorPage;
