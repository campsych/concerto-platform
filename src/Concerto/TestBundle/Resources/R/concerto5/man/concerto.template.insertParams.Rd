\name{concerto.template.insertParams}
\alias{concerto.template.insertParams}
\title{
Template helper function.
}
\description{
Fills template HTML with given parameters.
}
\usage{
concerto.template.insertParams(html, params = list(), removeMissing=T)
}
\arguments{
  \item{html}{
HTML template.
}
  \item{params}{
Named list with parameters.
}
  \item{removeMissing}{
Should missing insert params be cleared?
}
}
\details{
}
\value{
HTML with inserted parameters.
}
\references{
}
\author{
Przemyslaw Lis
}
\note{
}

\seealso{
}
\examples{
html <- "<html><body>Inserted variable: {{param1}}</body></html>"
html <- concerto.template.insertParams(html,list(param1="value"))
}

\keyword{ template }
\keyword{ HTML }
