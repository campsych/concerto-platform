\name{concerto.template.join}
\alias{concerto.template.join}
\title{
Template helper function.
}
\description{
Combines template CSS, JS and HTML.
}
\usage{
concerto.template.join(html="", css="", js="", templateId=NULL, params = list())
}
\arguments{
  \item{html}{
HTML
}
  \item{css}{
CSS
}
  \item{js}{
javascript
}
  \item{templateId}{
Template id.
}
  \item{params}{
Named list with parameters.
}
}
\details{
}
\value{
Combined HTML.
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
html <- concerto.template.join(templateId=1)
}

\keyword{ template }
\keyword{ HTML }
